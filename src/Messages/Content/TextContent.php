<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Messages\Content;

use AsmaaGamal\MetaMessaging\Contracts\MessageContent;
use AsmaaGamal\MetaMessaging\Enums\Capability;

/**
 * A plain text message body.
 */
final readonly class TextContent implements MessageContent
{
    public function __construct(public string $text) {}

    public function toPayload(): array
    {
        return ['text' => $this->text];
    }

    public function capability(): Capability
    {
        return Capability::Text;
    }

    public function describe(): string
    {
        return 'text';
    }

    public function length(): int
    {
        return mb_strlen($this->text);
    }
}
