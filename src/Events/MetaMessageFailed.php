<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Events;

use AsmaaGamal\MetaMessaging\Responses\MessageResponse;
use AsmaaGamal\MetaMessaging\Responses\MetaError;
use AsmaaGamal\MetaMessaging\Transport\MetaRequest;

/**
 * Raised whenever a call fails, before any exception is thrown.
 *
 * Listen for this to record delivery failures without wrapping every send in a
 * try/catch — the error carries the same structure as the exception would.
 */
final readonly class MetaMessageFailed
{
    public function __construct(
        public MetaRequest $request,
        public MessageResponse $response,
    ) {}

    public function error(): ?MetaError
    {
        return $this->response->error;
    }
}
