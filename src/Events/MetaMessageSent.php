<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Events;

use AsmaaGamal\MetaMessaging\Responses\MessageResponse;
use AsmaaGamal\MetaMessaging\Transport\MetaRequest;

/**
 * Raised after Meta accepts a call. Useful for audit logs and analytics.
 */
final readonly class MetaMessageSent
{
    public function __construct(
        public MetaRequest $request,
        public MessageResponse $response,
    ) {}

    public function messageId(): ?string
    {
        return $this->response->messageId();
    }
}
