<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Transport;

use AsmaaGamal\MetaMessaging\Contracts\Transport;
use AsmaaGamal\MetaMessaging\Exceptions\ErrorMapper;
use AsmaaGamal\MetaMessaging\Responses\MessageResponse;
use AsmaaGamal\MetaMessaging\Responses\MetaError;
use AsmaaGamal\MetaMessaging\Support\Redactor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * The real transport, built on Laravel's HTTP client.
 *
 * Because it goes through Illuminate\Http\Client, the whole package is testable
 * with Http::fake() as well as with the package's own FakeTransport.
 */
final class HttpTransport implements Transport
{
    /**
     * @param  array<string, mixed>  $options  the meta-messaging.http config block
     */
    public function __construct(
        private readonly HttpFactory $http,
        private readonly array $options = [],
        private readonly ?LoggerInterface $logger = null,
    ) {}

    public function send(MetaRequest $request): MessageResponse
    {
        $this->log('Meta request', $request->toArray());

        try {
            $response = $this->dispatch($request);
        } catch (ConnectionException $e) {
            return $this->fail($request, ErrorMapper::transport($e->getMessage(), $request));
        } catch (Throwable $e) {
            return $this->fail($request, ErrorMapper::transport($e->getMessage(), $request));
        }

        return $this->interpret($request, $response);
    }

    private function dispatch(MetaRequest $request): Response
    {
        $pending = $this->pending();

        if ($request->hasFiles()) {
            return $this->dispatchMultipart($pending, $request);
        }

        return $pending->send($request->method, $request->url(), [
            'json' => $request->body(),
        ]);
    }

    /**
     * Meta accepts local uploads as multipart, with the JSON payload flattened
     * into ordinary form fields alongside the file.
     */
    private function dispatchMultipart(PendingRequest $pending, MetaRequest $request): Response
    {
        foreach ($request->files as $field => $path) {
            $pending = $pending->attach($field, (string) file_get_contents($path), basename($path));
        }

        $form = [];

        foreach ($request->body() as $key => $value) {
            $form[$key] = is_array($value) ? json_encode($value) : $value;
        }

        return $pending->asMultipart()->post($request->url(), $form);
    }

    private function pending(): PendingRequest
    {
        $pending = $this->http
            ->timeout((int) ($this->options['timeout'] ?? 30))
            ->connectTimeout((int) ($this->options['connect_timeout'] ?? 10))
            ->acceptJson();

        $retries = (int) ($this->options['retries'] ?? 0);

        if ($retries > 0) {
            // Only connection-level faults are retried here. Meta-level failures
            // come back as a 4xx that this client must not blindly repeat — a
            // blocked recipient never becomes unblocked on the second attempt.
            $pending = $pending->retry(
                $retries + 1,
                (int) ($this->options['retry_delay'] ?? 500),
                throw: false,
            );
        }

        return $pending;
    }

    private function interpret(MetaRequest $request, Response $response): MessageResponse
    {
        $status = $response->status();
        $decoded = json_decode($response->body(), true);

        if (! is_array($decoded)) {
            return $this->fail($request, ErrorMapper::malformed($response->body(), $status, $request));
        }

        if ($response->successful() && ! isset($decoded['error'])) {
            $this->log('Meta response', ['status' => $status, 'body' => Redactor::scrub($decoded)]);

            return MessageResponse::success($request->channel(), $decoded, $status);
        }

        return $this->fail(
            $request,
            ErrorMapper::fromBody($decoded, $status, $request),
            $decoded,
            $status,
        );
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    private function fail(
        MetaRequest $request,
        MetaError $error,
        array $raw = [],
        ?int $status = null,
    ): MessageResponse {
        $this->log('Meta request failed', $error->toArray());

        return MessageResponse::failure($request->channel(), $error, $raw, $status);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function log(string $message, array $context): void
    {
        $this->logger?->debug($message, $context);
    }
}
