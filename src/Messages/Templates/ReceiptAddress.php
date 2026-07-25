<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Messages\Templates;

/**
 * The shipping address block of a receipt template.
 */
final readonly class ReceiptAddress
{
    public function __construct(
        public string $street1,
        public string $city,
        public string $postalCode,
        public string $state,
        public string $country,
        public ?string $street2 = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return array_filter([
            'street_1' => $this->street1,
            'street_2' => $this->street2,
            'city' => $this->city,
            'postal_code' => $this->postalCode,
            'state' => $this->state,
            'country' => $this->country,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
