<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Messages\Templates;

/**
 * The totals block of a receipt template. Only total_cost is required by Meta.
 */
final readonly class ReceiptSummary
{
    private function __construct(
        public float $totalCost,
        public ?float $subtotal = null,
        public ?float $shippingCost = null,
        public ?float $totalTax = null,
    ) {}

    public static function make(float $totalCost): self
    {
        return new self($totalCost);
    }

    public function subtotal(float $subtotal): self
    {
        return new self($this->totalCost, $subtotal, $this->shippingCost, $this->totalTax);
    }

    public function shipping(float $shippingCost): self
    {
        return new self($this->totalCost, $this->subtotal, $shippingCost, $this->totalTax);
    }

    public function tax(float $totalTax): self
    {
        return new self($this->totalCost, $this->subtotal, $this->shippingCost, $totalTax);
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return array_filter([
            'subtotal' => $this->subtotal,
            'shipping_cost' => $this->shippingCost,
            'total_tax' => $this->totalTax,
            'total_cost' => $this->totalCost,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
