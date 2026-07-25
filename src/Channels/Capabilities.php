<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Channels;

use AsmaaGamal\MetaMessaging\Enums\Capability;

/**
 * The set of features one channel supports.
 *
 * Declaring this once per channel keeps every "Instagram cannot do that" check
 * in a single place, and lets error messages list the alternatives that do work.
 */
final readonly class Capabilities
{
    /** @var array<string, true> */
    private array $lookup;

    /**
     * @param  array<int, Capability>  $capabilities
     */
    public function __construct(array $capabilities)
    {
        $lookup = [];

        foreach ($capabilities as $capability) {
            $lookup[$capability->value] = true;
        }

        $this->lookup = $lookup;
    }

    public function has(Capability $capability): bool
    {
        return isset($this->lookup[$capability->value]);
    }

    public function missing(Capability $capability): bool
    {
        return ! $this->has($capability);
    }

    /**
     * The template types this channel accepts, named as they appear in errors.
     *
     * @return array<int, string>
     */
    public function supportedTemplates(): array
    {
        $names = [
            Capability::GenericTemplate->value => 'generic',
            Capability::ButtonTemplate->value => 'button',
            Capability::MediaTemplate->value => 'media',
            Capability::ReceiptTemplate->value => 'receipt',
            Capability::ProductTemplate->value => 'product',
            Capability::CustomerFeedbackTemplate->value => 'customer feedback',
        ];

        return array_values(array_intersect_key($names, $this->lookup));
    }

    /**
     * @return array<int, string>
     */
    public function all(): array
    {
        return array_keys($this->lookup);
    }
}
