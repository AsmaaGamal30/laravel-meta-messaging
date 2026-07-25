<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Validation;

use AsmaaGamal\MetaMessaging\Channels\MessagingChannel;
use AsmaaGamal\MetaMessaging\Enums\Channel;
use AsmaaGamal\MetaMessaging\Exceptions\MessageValidationException;

/**
 * Instagram accepts only the heart reaction from a business account.
 *
 * Anything else is rejected by Meta with an unhelpful parameter error, so the
 * allowed values are enumerated here instead.
 */
final class ReactionValidator
{
    /**
     * Values Instagram accepts, in either the named or emoji form.
     *
     * @var array<int, string>
     */
    private const INSTAGRAM_ALLOWED = ['love', '❤', '❤️'];

    /**
     * @throws MessageValidationException
     */
    public function validate(string $reaction, MessagingChannel $channel): void
    {
        if ($channel->channel() !== Channel::Instagram) {
            return;
        }

        if (! in_array($reaction, self::INSTAGRAM_ALLOWED, true)) {
            throw MessageValidationException::instagramReactionUnsupported($reaction);
        }
    }
}
