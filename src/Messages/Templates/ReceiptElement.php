<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Messages\Templates;

/**
 * One purchased line item on a receipt template.
 */
final readonly class ReceiptElement
{
    private function __construct(
        public string $title,
        public float $price,
        public ?string $subtitle = null,
        public ?int $quantity = null,
        public ?string $currency = null,
        public ?string $imageUrl = null,
    ) {}

    public static function make(string $title, float $price): self
    {
        return new self($title, $price);
    }

    public function subtitle(string $subtitle): self
    {
        return new self($this->title, $this->price, $subtitle, $this->quantity, $this->currency, $this->imageUrl);
    }

    public function quantity(int $quantity): self
    {
        return new self($this->title, $this->price, $this->subtitle, $quantity, $this->currency, $this->imageUrl);
    }

    public function currency(string $currency): self
    {
        return new self($this->title, $this->price, $this->subtitle, $this->quantity, $currency, $this->imageUrl);
    }

    public function image(string $url): self
    {
        return new self($this->title, $this->price, $this->subtitle, $this->quantity, $this->currency, $url);
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return array_filter([
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'currency' => $this->currency,
            'image_url' => $this->imageUrl,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
