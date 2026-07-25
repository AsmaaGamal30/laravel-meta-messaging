<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Contracts;

use AsmaaGamal\MetaMessaging\Exceptions\MessageValidationException;

/**
 * A button attached to a template card or a button template.
 */
interface Button
{
    /**
     * Render as one entry of a "buttons" array.
     *
     * @return array<string, mixed>
     */
    public function toPayload(): array;

    /**
     * Check this button's own limits.
     *
     * @throws MessageValidationException
     */
    public function validate(): void;
}
