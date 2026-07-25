<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Messages\Templates;

use AsmaaGamal\MetaMessaging\Contracts\Template;
use AsmaaGamal\MetaMessaging\Enums\Capability;
use AsmaaGamal\MetaMessaging\Exceptions\MessageValidationException;

/**
 * Cards drawn from a product catalog. Supported on Messenger and Instagram.
 *
 * Only the retailer product IDs are sent; Meta renders the title, price, and
 * image from the catalog itself.
 */
final readonly class ProductTemplate implements Template
{
    public const MAX_PRODUCTS = 10;

    /**
     * @param  array<int, string>  $productIds
     */
    private function __construct(public array $productIds = []) {}

    /**
     * @param  array<int, string>  $productIds
     */
    public static function make(array $productIds = []): self
    {
        return new self(array_values($productIds));
    }

    public function product(string $productId): self
    {
        return new self([...$this->productIds, $productId]);
    }

    public function toPayload(): array
    {
        return [
            'template_type' => 'product',
            'elements' => array_map(
                static fn (string $id): array => ['id' => $id],
                $this->productIds,
            ),
        ];
    }

    public function capability(): Capability
    {
        return Capability::ProductTemplate;
    }

    public function type(): string
    {
        return 'product';
    }

    public function validate(): void
    {
        if ($this->productIds === []) {
            throw MessageValidationException::make(
                'empty_message',
                'A product template needs at least one product ID.',
            );
        }

        if (count($this->productIds) > self::MAX_PRODUCTS) {
            throw MessageValidationException::tooManyCards(count($this->productIds), self::MAX_PRODUCTS);
        }
    }
}
