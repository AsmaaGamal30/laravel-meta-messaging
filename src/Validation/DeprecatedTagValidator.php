<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Validation;

use AsmaaGamal\MetaMessaging\Channels\MessagingChannel;
use AsmaaGamal\MetaMessaging\Contracts\Validator;
use AsmaaGamal\MetaMessaging\Exceptions\DeprecatedMessageTagException;
use AsmaaGamal\MetaMessaging\Messages\MessageBuilder;

/**
 * Catches message tags Meta retired on 27 April 2026.
 *
 * The single most valuable check in the package. Meta answers a retired tag with
 * a bare "(#100) Invalid parameter" that never names the tag, so applications
 * that worked for years break with an error nobody can trace. Refusing locally
 * turns that into a sentence naming the tag, the date, and the replacement.
 */
final class DeprecatedTagValidator implements Validator
{
    public function validate(MessageBuilder $message, MessagingChannel $channel): void
    {
        $tag = $message->messageTag();

        if ($tag !== null && $tag->isDeprecated()) {
            throw DeprecatedMessageTagException::make($tag);
        }
    }
}
