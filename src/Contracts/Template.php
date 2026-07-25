<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Contracts;

use AsmaaGamal\MetaMessaging\Enums\Capability;
use AsmaaGamal\MetaMessaging\Exceptions\MessageValidationException;

/**
 * A structured template rendered inside a template attachment.
 */
interface Template
{
    /**
     * Render as the template attachment's "payload" object.
     *
     * @return array<string, mixed>
     */
    public function toPayload(): array;

    /**
     * The capability a channel needs in order to accept this template.
     */
    public function capability(): Capability;

    /**
     * Meta's template_type value, e.g. "generic".
     */
    public function type(): string;

    /**
     * Check this template's own limits.
     *
     * Called before the request is built. Throws a MessageValidationException
     * describing the first thing that is wrong.
     *
     * @throws MessageValidationException
     */
    public function validate(): void;
}
