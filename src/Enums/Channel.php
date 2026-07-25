<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Enums;

/**
 * The messaging surfaces this package supports.
 */
enum Channel: string
{
    case Facebook = 'facebook';
    case Instagram = 'instagram';

    /**
     * Human readable name, used when building error messages.
     */
    public function label(): string
    {
        return match ($this) {
            self::Facebook => 'Facebook Messenger',
            self::Instagram => 'Instagram',
        };
    }

    /**
     * Maximum number of characters allowed in a single text message.
     */
    public function textLimit(): int
    {
        return match ($this) {
            self::Facebook => 2000,
            self::Instagram => 1000,
        };
    }
}
