<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Messages\Buttons;

use AsmaaGamal\MetaMessaging\Contracts\Button;
use AsmaaGamal\MetaMessaging\Exceptions\MessageValidationException;

/**
 * Dials a phone number when tapped. The number must be in E.164 form.
 */
final readonly class CallButton implements Button
{
    public const TITLE_LIMIT = 20;

    private function __construct(
        public string $title,
        public string $phoneNumber,
    ) {}

    public static function make(string $title, string $phoneNumber): self
    {
        return new self($title, $phoneNumber);
    }

    public function toPayload(): array
    {
        return [
            'type' => 'phone_number',
            'title' => $this->title,
            'payload' => $this->phoneNumber,
        ];
    }

    public function validate(): void
    {
        if (mb_strlen($this->title) > self::TITLE_LIMIT) {
            throw MessageValidationException::titleTooLong('button_title_too_long', $this->title, self::TITLE_LIMIT);
        }
    }
}
