<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Jobs;

use AsmaaGamal\MetaMessaging\Contracts\Transport;
use AsmaaGamal\MetaMessaging\Events\MetaMessageFailed;
use AsmaaGamal\MetaMessaging\Events\MetaMessageSent;
use AsmaaGamal\MetaMessaging\Exceptions\ErrorMapper;
use AsmaaGamal\MetaMessaging\Transport\MetaRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Performs a Graph API call on a worker.
 *
 * Retries only what is worth retrying: a rate limit or a network fault comes
 * back and tries again, while a blocked recipient or an expired token fails the
 * job immediately, because no number of attempts will change the answer.
 *
 * Implements ShouldBeEncrypted because the serialized MetaRequest carries the
 * account's access token and app secret. Without encryption those credentials
 * would sit in plain text in the queue backend (database, Redis, SQS, …) and
 * in the failed_jobs table if the job fails.
 */
final class SendMetaMessageJob implements ShouldQueue, ShouldBeEncrypted
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries;

    public function __construct(public readonly MetaRequest $request)
    {
        /** @var array<string, mixed> $config */
        $config = function_exists('config') ? (array) config('meta-messaging.queue', []) : [];

        $this->tries = (int) ($config['tries'] ?? 3);
        $this->connection = is_string($config['connection'] ?? null) ? $config['connection'] : null;
        $this->queue = is_string($config['queue'] ?? null) ? $config['queue'] : null;
    }

    public function handle(Transport $transport, Dispatcher $events): void
    {
        $response = $transport->send($this->request);

        if ($response->successful) {
            $events->dispatch(new MetaMessageSent($this->request, $response));

            return;
        }

        $events->dispatch(new MetaMessageFailed($this->request, $response));

        $error = $response->error;

        if ($error === null) {
            return;
        }

        if (! $error->isRetryable()) {
            // Nothing about a permanent failure improves on a second attempt, so
            // stop consuming attempts and let the job fail for good.
            $this->fail(ErrorMapper::toException($error));

            return;
        }

        throw ErrorMapper::toException($error);
    }

    /**
     * Back off progressively while a rate limit clears.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 60, 300];
    }
}
