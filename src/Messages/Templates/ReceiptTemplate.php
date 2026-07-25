<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Messages\Templates;

use AsmaaGamal\MetaMessaging\Contracts\Template;
use AsmaaGamal\MetaMessaging\Enums\Capability;
use AsmaaGamal\MetaMessaging\Exceptions\MessageValidationException;

/**
 * An order confirmation. Facebook Messenger only.
 */
final readonly class ReceiptTemplate implements Template
{
    /**
     * @param  array<int, ReceiptElement>  $elements
     * @param  array<int, array{name: string, amount: float}>  $adjustments
     */
    private function __construct(
        public string $recipientName,
        public string $orderNumber,
        public string $currency,
        public string $paymentMethod,
        public ReceiptSummary $summary,
        public array $elements = [],
        public ?ReceiptAddress $address = null,
        public array $adjustments = [],
        public ?string $orderUrl = null,
        public ?string $merchantName = null,
        public ?int $timestamp = null,
    ) {}

    /**
     * @param  string  $paymentMethod  free text, e.g. "Visa 1234"
     */
    public static function make(
        string $recipientName,
        string $orderNumber,
        string $currency,
        string $paymentMethod,
        ReceiptSummary $summary,
    ): self {
        return new self($recipientName, $orderNumber, $currency, $paymentMethod, $summary);
    }

    public function element(ReceiptElement $element): self
    {
        return $this->with(elements: [...$this->elements, $element]);
    }

    /**
     * @param  array<int, ReceiptElement>  $elements
     */
    public function elements(array $elements): self
    {
        return $this->with(elements: $elements);
    }

    public function address(ReceiptAddress $address): self
    {
        return $this->with(address: $address);
    }

    /**
     * A discount or credit applied to the order.
     */
    public function adjustment(string $name, float $amount): self
    {
        return $this->with(adjustments: [...$this->adjustments, ['name' => $name, 'amount' => $amount]]);
    }

    public function orderUrl(string $url): self
    {
        return $this->with(orderUrl: $url);
    }

    /**
     * Shows the merchant name in place of the Page name.
     */
    public function merchantName(string $name): self
    {
        return $this->with(merchantName: $name);
    }

    public function timestamp(int $unixTimestamp): self
    {
        return $this->with(timestamp: $unixTimestamp);
    }

    public function toPayload(): array
    {
        $payload = [
            'template_type' => 'receipt',
            'recipient_name' => $this->recipientName,
            'order_number' => $this->orderNumber,
            'currency' => $this->currency,
            'payment_method' => $this->paymentMethod,
            'summary' => $this->summary->toPayload(),
        ];

        if ($this->merchantName !== null) {
            $payload['merchant_name'] = $this->merchantName;
        }

        if ($this->timestamp !== null) {
            $payload['timestamp'] = (string) $this->timestamp;
        }

        if ($this->orderUrl !== null) {
            $payload['order_url'] = $this->orderUrl;
        }

        if ($this->elements !== []) {
            $payload['elements'] = array_map(
                static fn (ReceiptElement $element): array => $element->toPayload(),
                $this->elements,
            );
        }

        if ($this->address !== null) {
            $payload['address'] = $this->address->toPayload();
        }

        if ($this->adjustments !== []) {
            $payload['adjustments'] = $this->adjustments;
        }

        return $payload;
    }

    public function capability(): Capability
    {
        return Capability::ReceiptTemplate;
    }

    public function type(): string
    {
        return 'receipt';
    }

    public function validate(): void
    {
        if (trim($this->orderNumber) === '') {
            throw MessageValidationException::make(
                'empty_message',
                'A receipt template needs an order number.',
            );
        }
    }

    /**
     * @param  array<int, ReceiptElement>|null  $elements
     * @param  array<int, array{name: string, amount: float}>|null  $adjustments
     */
    private function with(
        ?array $elements = null,
        ?ReceiptAddress $address = null,
        ?array $adjustments = null,
        ?string $orderUrl = null,
        ?string $merchantName = null,
        ?int $timestamp = null,
    ): self {
        return new self(
            $this->recipientName,
            $this->orderNumber,
            $this->currency,
            $this->paymentMethod,
            $this->summary,
            $elements ?? $this->elements,
            $address ?? $this->address,
            $adjustments ?? $this->adjustments,
            $orderUrl ?? $this->orderUrl,
            $merchantName ?? $this->merchantName,
            $timestamp ?? $this->timestamp,
        );
    }
}
