<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Messages\Buttons;

use AsmaaGamal\MetaMessaging\Contracts\Button;
use AsmaaGamal\MetaMessaging\Exceptions\MessageValidationException;

/**
 * Starts the account linking flow at your authorisation URL.
 */
final readonly class LoginButton implements Button
{
    private function __construct(public string $url) {}

    public static function make(string $url): self
    {
        return new self($url);
    }

    public function toPayload(): array
    {
        return [
            'type' => 'account_link',
            'url' => $this->url,
        ];
    }

    public function validate(): void
    {
        if (filter_var($this->url, FILTER_VALIDATE_URL) === false) {
            throw MessageValidationException::invalidUrl($this->url);
        }
    }
}
