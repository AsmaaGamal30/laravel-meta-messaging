<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Messages\Templates;

use AsmaaGamal\MetaMessaging\Contracts\Template;
use AsmaaGamal\MetaMessaging\Enums\Capability;
use AsmaaGamal\MetaMessaging\Exceptions\MessageValidationException;

/**
 * A horizontally scrollable carousel of cards.
 *
 * Supported on both Facebook Messenger and Instagram.
 */
final readonly class GenericTemplate implements Template
{
    public const MAX_CARDS = 10;

    /**
     * @param  array<int, Card>  $cards
     */
    private function __construct(
        public array $cards = [],
        public bool $squareImages = false,
        public bool $sharable = false,
    ) {}

    public static function make(): self
    {
        return new self;
    }

    public function card(Card $card): self
    {
        return new self([...$this->cards, $card], $this->squareImages, $this->sharable);
    }

    /**
     * @param  array<int, Card>  $cards
     */
    public function cards(array $cards): self
    {
        return new self($cards, $this->squareImages, $this->sharable);
    }

    /**
     * Crop card images to a square rather than the default 1.91:1.
     */
    public function square(bool $square = true): self
    {
        return new self($this->cards, $square, $this->sharable);
    }

    /**
     * Let recipients forward the template.
     */
    public function sharable(bool $sharable = true): self
    {
        return new self($this->cards, $this->squareImages, $sharable);
    }

    public function toPayload(): array
    {
        $payload = [
            'template_type' => 'generic',
            'elements' => array_map(
                static fn (Card $card): array => $card->toPayload(),
                $this->cards,
            ),
        ];

        if ($this->squareImages) {
            $payload['image_aspect_ratio'] = 'square';
        }

        if ($this->sharable) {
            $payload['sharable'] = true;
        }

        return $payload;
    }

    public function capability(): Capability
    {
        return Capability::GenericTemplate;
    }

    public function type(): string
    {
        return 'generic';
    }

    public function validate(): void
    {
        if ($this->cards === []) {
            throw MessageValidationException::make(
                'empty_message',
                'A generic template needs at least one card.',
            );
        }

        if (count($this->cards) > self::MAX_CARDS) {
            throw MessageValidationException::tooManyCards(count($this->cards), self::MAX_CARDS);
        }

        foreach ($this->cards as $card) {
            $card->validate();
        }
    }
}
