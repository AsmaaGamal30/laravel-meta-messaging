<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Transport;

use AsmaaGamal\MetaMessaging\Contracts\Transport;
use AsmaaGamal\MetaMessaging\Exceptions\ErrorMapper;
use AsmaaGamal\MetaMessaging\Responses\MessageResponse;
use Closure;
use PHPUnit\Framework\Assert;

/**
 * Records requests instead of sending them, and replays canned responses.
 *
 * Activated by Meta::fake(). Sits at the same seam as the real transport, so
 * tests exercise the builder, validators, and error mapping exactly as
 * production does — only the network is missing.
 */
final class FakeTransport implements Transport
{
    /** @var array<int, MetaRequest> */
    private array $requests = [];

    /** @var array<int, array{matcher: Closure(MetaRequest): bool, factory: Closure(MetaRequest): MessageResponse}> */
    private array $stubs = [];

    public function send(MetaRequest $request): MessageResponse
    {
        $this->requests[] = $request;

        foreach ($this->stubs as $stub) {
            if (($stub['matcher'])($request)) {
                return ($stub['factory'])($request);
            }
        }

        return MessageResponse::success($request->channel(), [
            'recipient_id' => $this->recipientId($request),
            'message_id' => 'm_fake_'.count($this->requests),
        ]);
    }

    /**
     * Answer the next matching request with a successful body.
     *
     * @param  array<string, mixed>  $body
     * @param  (Closure(MetaRequest): bool)|null  $when
     */
    public function respondWith(array $body, ?Closure $when = null): self
    {
        return $this->stub(
            $when,
            static fn (MetaRequest $request): MessageResponse => MessageResponse::success($request->channel(), $body),
        );
    }

    /**
     * Answer the next matching request with a Graph API error.
     *
     * Runs through the real error mapper, so the test sees the same typed
     * exception and hint that production would produce.
     *
     * @param  (Closure(MetaRequest): bool)|null  $when
     */
    public function respondWithError(
        int $code,
        string $message,
        ?int $subcode = null,
        int $status = 400,
        ?Closure $when = null,
    ): self {
        $body = ['error' => array_filter([
            'message' => $message,
            'code' => $code,
            'error_subcode' => $subcode,
            'type' => 'OAuthException',
            'fbtrace_id' => 'FAKE_TRACE',
        ], static fn (mixed $value): bool => $value !== null)];

        return $this->stub(
            $when,
            static fn (MetaRequest $request): MessageResponse => MessageResponse::failure(
                $request->channel(),
                ErrorMapper::fromBody($body, $status, $request),
                $body,
                $status,
            ),
        );
    }

    /**
     * Every request made, in order.
     *
     * @return array<int, MetaRequest>
     */
    public function requests(): array
    {
        return $this->requests;
    }

    public function lastRequest(): ?MetaRequest
    {
        return $this->requests === [] ? null : $this->requests[count($this->requests) - 1];
    }

    /**
     * Assert a request matching the callback was made.
     *
     * @param  (Closure(MetaRequest): bool)|null  $callback
     */
    public function assertSent(?Closure $callback = null): void
    {
        $matches = $callback === null
            ? $this->requests
            : array_filter($this->requests, $callback);

        Assert::assertNotEmpty($matches, 'No matching Meta request was sent.');
    }

    /**
     * Assert no request matching the callback was made.
     *
     * @param  (Closure(MetaRequest): bool)|null  $callback
     */
    public function assertNotSent(?Closure $callback = null): void
    {
        $matches = $callback === null
            ? $this->requests
            : array_filter($this->requests, $callback);

        Assert::assertEmpty($matches, 'An unexpected Meta request was sent.');
    }

    public function assertNothingSent(): void
    {
        Assert::assertEmpty($this->requests, 'Expected no Meta requests, but some were sent.');
    }

    public function assertSentCount(int $count): void
    {
        Assert::assertCount($count, $this->requests);
    }

    /**
     * @param  (Closure(MetaRequest): bool)|null  $when
     * @param  Closure(MetaRequest): MessageResponse  $factory
     */
    private function stub(?Closure $when, Closure $factory): self
    {
        $this->stubs[] = [
            'matcher' => $when ?? static fn (): bool => true,
            'factory' => $factory,
        ];

        return $this;
    }

    private function recipientId(MetaRequest $request): string
    {
        $recipient = $request->payload['recipient'] ?? [];

        return is_array($recipient) && is_string($recipient['id'] ?? null)
            ? $recipient['id']
            : 'fake_recipient';
    }
}
