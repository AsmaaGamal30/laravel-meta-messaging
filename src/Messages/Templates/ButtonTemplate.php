<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Messages\Templates;

use AsmaaGamal\MetaMessaging\Contracts\Button;
use AsmaaGamal\MetaMessaging\Contracts\Template;
use AsmaaGamal\MetaMessaging\Enums\Capability;
use AsmaaGamal\MetaMessaging\Exceptions\MessageValidationException;

/**
 * A block of text with up to three buttons under it.
 *
 * Supported on both Facebook Messenger and Instagram.
 */
final readonly class ButtonTemplate implements Template
{
    public const MAX_BUTTONS = 3;

    public const TEXT_LIMIT = 640;

    /**
     * @param  array<int, Button>  $buttons
     */
    private function __construct(
        public string $text,
        public array $buttons = [],
    ) {}

    public static function make(string $text): self
    {
        return new self($text);
    }

    public function button(Button $button): self
    {
        return new self($this->text, [...$this->buttons, $button]);
    }

    /**
     * @param  array<int, Button>  $buttons
     */
    public function buttons(array $buttons): self
    {
        return new self($this->text, $buttons);
    }

    public function toPayload(): array
    {
        return [
            'template_type' => 'button',
            'text' => $this->text,
            'buttons' => array_map(
                static fn (Button $button): array => $button->toPayload(),
                $this->buttons,
            ),
        ];
    }

    public function capability(): Capability
    {
        return Capability::ButtonTemplate;
    }

    public function type(): string
    {
        return 'button';
    }

    public function validate(): void
    {
        if ($this->buttons === []) {
            throw MessageValidationException::make(
                'empty_message',
                'A button template needs at least one button.',
            );
        }

        if (count($this->buttons) > self::MAX_BUTTONS) {
            throw MessageValidationException::tooManyButtons('button template', count($this->buttons), self::MAX_BUTTONS);
        }

        foreach ($this->buttons as $button) {
            $button->validate();
        }
    }
}
