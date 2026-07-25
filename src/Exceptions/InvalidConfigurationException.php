<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Exceptions;

use AsmaaGamal\MetaMessaging\Enums\Channel;
use AsmaaGamal\MetaMessaging\Responses\MetaError;
use AsmaaGamal\MetaMessaging\Support\Hint;

/**
 * The package is misconfigured — raised while resolving an account, before any
 * request is built.
 */
final class InvalidConfigurationException extends MetaMessagingException
{
    /**
     * A required credential is absent from the account config.
     */
    public static function missingCredential(Channel $channel, string $account, string $key): self
    {
        return new self(MetaError::local(
            key: 'missing_credential',
            message: sprintf('Missing [%s] for %s account [%s].', $key, $channel->value, $account),
            hint: Hint::get('missing_credential', [
                'key' => $key,
                'channel' => $channel->label(),
                'channel_key' => $channel->value,
                'account' => $account,
            ]),
            channel: $channel,
            context: ['account' => $account, 'missing' => $key],
        ));
    }

    /**
     * The requested account name is not present in the config.
     *
     * @param  array<int, string>  $available
     */
    public static function unknownAccount(Channel $channel, string $account, array $available): self
    {
        return new self(MetaError::local(
            key: 'unknown_account',
            message: sprintf('Unknown %s account [%s].', $channel->value, $account),
            hint: Hint::get('unknown_account', [
                'channel' => $channel->label(),
                'account' => $account,
                'available' => $available === [] ? 'none' : implode(', ', $available),
            ]),
            channel: $channel,
            context: ['account' => $account, 'available' => $available],
        ));
    }

    /**
     * The Graph API version string is malformed.
     */
    public static function invalidVersion(string $version): self
    {
        return new self(MetaError::local(
            key: 'invalid_version',
            message: sprintf('Invalid Graph API version [%s].', $version),
            hint: Hint::get('invalid_version', ['version' => $version]),
            context: ['version' => $version],
        ));
    }

    /**
     * The Instagram login_type is not one this package knows.
     */
    public static function invalidLoginType(string $account, mixed $value): self
    {
        $printable = is_scalar($value) ? (string) $value : gettype($value);

        return new self(MetaError::local(
            key: 'invalid_login_type',
            message: sprintf('Invalid Instagram login_type [%s].', $printable),
            hint: Hint::get('invalid_login_type', ['account' => $account, 'value' => $printable]),
            channel: Channel::Instagram,
            context: ['account' => $account, 'login_type' => $printable],
        ));
    }
}
