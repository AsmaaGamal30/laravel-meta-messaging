<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Exceptions;

use AsmaaGamal\MetaMessaging\Enums\Capability;
use AsmaaGamal\MetaMessaging\Enums\Channel;
use AsmaaGamal\MetaMessaging\Responses\MetaError;
use AsmaaGamal\MetaMessaging\Support\Hint;

/**
 * The requested feature does not exist on this channel.
 *
 * Facebook Messenger and Instagram share a request shape but not a feature set.
 * Rather than let Meta answer with "(#100) Invalid parameter", the capability
 * validator refuses up front and names what the channel does support.
 */
final class UnsupportedFeatureException extends MetaMessagingException
{
    /**
     * @param  array<int, string>  $alternatives  e.g. the templates that do work here
     */
    public static function make(Channel $channel, Capability $capability, array $alternatives = []): self
    {
        $isTemplate = str_ends_with($capability->value, '_template');

        $hint = $isTemplate && $alternatives !== []
            ? Hint::get('unsupported_template', [
                'channel' => $channel->label(),
                'feature' => $capability->label(),
                'supported' => implode(', ', $alternatives),
            ])
            : Hint::get('unsupported_feature', [
                'channel' => $channel->label(),
                'feature' => $capability->label(),
            ]);

        return new self(MetaError::local(
            key: 'unsupported_feature',
            message: sprintf('%s does not support %s.', $channel->label(), $capability->label()),
            hint: $hint,
            channel: $channel,
            context: [
                'capability' => $capability->value,
                'alternatives' => $alternatives,
            ],
        ));
    }
}
