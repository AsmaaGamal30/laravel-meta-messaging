<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Messages\Content;

use AsmaaGamal\MetaMessaging\Contracts\MessageContent;
use AsmaaGamal\MetaMessaging\Contracts\Template;
use AsmaaGamal\MetaMessaging\Enums\Capability;

/**
 * Wraps a template in the attachment envelope the Send API expects.
 */
final readonly class TemplateContent implements MessageContent
{
    public function __construct(public Template $template) {}

    public function toPayload(): array
    {
        return [
            'attachment' => [
                'type' => 'template',
                'payload' => $this->template->toPayload(),
            ],
        ];
    }

    public function capability(): Capability
    {
        return $this->template->capability();
    }

    public function describe(): string
    {
        return $this->template->type().' template';
    }
}
