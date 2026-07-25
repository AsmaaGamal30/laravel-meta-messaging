<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Contracts;

use AsmaaGamal\MetaMessaging\Enums\Capability;

/**
 * A thing that can occupy the "message" object of a Send API request.
 *
 * Each implementation knows two things and nothing else: how to render itself as
 * Meta expects, and which capability a channel must hold to accept it. That is
 * what lets the capability validator work without knowing any content types.
 */
interface MessageContent
{
    /**
     * Render as the Send API "message" object.
     *
     * @return array<string, mixed>
     */
    public function toPayload(): array;

    /**
     * The capability a channel needs in order to accept this content.
     */
    public function capability(): Capability;

    /**
     * A short noun for this content, used in error messages.
     */
    public function describe(): string;
}
