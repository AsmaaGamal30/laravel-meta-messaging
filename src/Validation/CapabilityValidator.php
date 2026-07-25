<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Validation;

use AsmaaGamal\MetaMessaging\Channels\MessagingChannel;
use AsmaaGamal\MetaMessaging\Contracts\Validator;
use AsmaaGamal\MetaMessaging\Enums\Capability;
use AsmaaGamal\MetaMessaging\Messages\MessageBuilder;

/**
 * Refuses anything the channel cannot do, and says what it can do instead.
 *
 * Without this, sending a receipt template to Instagram comes back as
 * "(#100) Invalid parameter" with no clue that the template type is at fault.
 */
final class CapabilityValidator implements Validator
{
    public function validate(MessageBuilder $message, MessagingChannel $channel): void
    {
        $content = $message->content();

        if ($content !== null) {
            $channel->requireCapability($content->capability());
        }

        if ($message->quickReplyList() !== []) {
            $channel->requireCapability(Capability::QuickReplies);
        }

        if ($message->replyToMessageId() !== null) {
            $channel->requireCapability(Capability::ReplyToMessage);
        }

        if ($message->messageTag() !== null) {
            $channel->requireCapability(Capability::MessageTags);
        }

        if ($message->isPrivateReply()) {
            $channel->requireCapability(Capability::PrivateReply);
        }
    }
}
