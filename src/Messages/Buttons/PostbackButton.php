<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Messages\Buttons;

use AsmaaGamal\MetaMessaging\Contracts\Button;
use AsmaaGamal\MetaMessaging\Exceptions\MessageValidationException;

/**
 * Sends a payload back to your webhook when tapped.
 */
final readonly class PostbackButton implements Button
{
    public const TITLE_LIMIT = 20;

    public const PAYLOAD_LIMIT = 1000;

    private function __construct(
        public string $title,
        public string $payload,
    ) {}

    public static function make(string $title, string $payload): self
    {
        return new self($title, $payload);
    }

    public function toPayload(): array
    {
        return [
            'type' => 'postback',
            'title' => $this->title,
            'payload' => $this->payload,
        ];
    }

    public function validate(): void
    {
        if (mb_strlen($this->title) > self::TITLE_LIMIT) {
            throw MessageValidationException::titleTooLong('button_title_too_long', $this->title, self::TITLE_LIMIT);
        }
    }
}
