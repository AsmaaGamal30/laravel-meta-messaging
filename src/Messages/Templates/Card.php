<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Messages\Templates;

use AsmaaGamal\MetaMessaging\Contracts\Button;
use AsmaaGamal\MetaMessaging\Exceptions\MessageValidationException;

/**
 * One element of a generic template carousel.
 *
 * Immutable: every builder method returns a new card, so a partially built card
 * can be reused as a starting point without surprises.
 */
final readonly class Card
{
    public const MAX_BUTTONS = 3;

    public const TITLE_LIMIT = 80;

    public const SUBTITLE_LIMIT = 80;

    /**
     * @param  array<int, Button>  $buttons
     */
    private function __construct(
        public string $title,
        public ?string $subtitle = null,
        public ?string $imageUrl = null,
        public array $buttons = [],
        public ?Button $defaultAction = null,
    ) {}

    public static function make(string $title): self
    {
        return new self($title);
    }

    public function subtitle(string $subtitle): self
    {
        return new self($this->title, $subtitle, $this->imageUrl, $this->buttons, $this->defaultAction);
    }

    public function image(string $url): self
    {
        return new self($this->title, $this->subtitle, $url, $this->buttons, $this->defaultAction);
    }

    /**
     * Add one button. Meta allows at most three per card.
     */
    public function button(Button $button): self
    {
        return new self(
            $this->title,
            $this->subtitle,
            $this->imageUrl,
            [...$this->buttons, $button],
            $this->defaultAction,
        );
    }

    /**
     * Replace every button on the card.
     *
     * @param  array<int, Button>  $buttons
     */
    public function buttons(array $buttons): self
    {
        return new self($this->title, $this->subtitle, $this->imageUrl, $buttons, $this->defaultAction);
    }

    /**
     * What happens when the card itself is tapped, rather than a button.
     */
    public function defaultAction(Button $action): self
    {
        return new self($this->title, $this->subtitle, $this->imageUrl, $this->buttons, $action);
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        $payload = ['title' => $this->title];

        if ($this->subtitle !== null) {
            $payload['subtitle'] = $this->subtitle;
        }

        if ($this->imageUrl !== null) {
            $payload['image_url'] = $this->imageUrl;
        }

        if ($this->defaultAction !== null) {
            // default_action is a web_url button without its title.
            $action = $this->defaultAction->toPayload();
            unset($action['title']);
            $payload['default_action'] = $action;
        }

        if ($this->buttons !== []) {
            $payload['buttons'] = array_map(
                static fn (Button $button): array => $button->toPayload(),
                $this->buttons,
            );
        }

        return $payload;
    }

    /**
     * @throws MessageValidationException
     */
    public function validate(): void
    {
        if (count($this->buttons) > self::MAX_BUTTONS) {
            throw MessageValidationException::tooManyButtons('card', count($this->buttons), self::MAX_BUTTONS);
        }

        foreach ($this->buttons as $button) {
            $button->validate();
        }

        $this->defaultAction?->validate();
    }
}
