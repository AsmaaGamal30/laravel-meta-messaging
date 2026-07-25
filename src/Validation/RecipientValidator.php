<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Validation;

use AsmaaGamal\MetaMessaging\Channels\MessagingChannel;
use AsmaaGamal\MetaMessaging\Contracts\Validator;
use AsmaaGamal\MetaMessaging\Exceptions\MessageValidationException;
use AsmaaGamal\MetaMessaging\Messages\MessageBuilder;

/**
 * There must be somebody to send to, and something to send.
 */
final class RecipientValidator implements Validator
{
    public function validate(MessageBuilder $message, MessagingChannel $channel): void
    {
        if ($message->recipient() === []) {
            throw MessageValidationException::missingRecipient($channel->channel());
        }

        if ($message->content() === null) {
            throw MessageValidationException::emptyMessage($channel->channel());
        }
    }
}
