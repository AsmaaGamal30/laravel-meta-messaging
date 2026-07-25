<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Messages\Buttons;

use AsmaaGamal\MetaMessaging\Contracts\Button;

/**
 * Unlinks the person's account. Carries no title or URL.
 */
final readonly class LogoutButton implements Button
{
    public static function make(): self
    {
        return new self;
    }

    public function toPayload(): array
    {
        return ['type' => 'account_unlink'];
    }

    public function validate(): void
    {
        // Nothing to check: this button has no configurable fields.
    }
}
