<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Validation;

use AsmaaGamal\MetaMessaging\Channels\MessagingChannel;
use AsmaaGamal\MetaMessaging\Contracts\Validator;
use AsmaaGamal\MetaMessaging\Exceptions\MessageValidationException;
use AsmaaGamal\MetaMessaging\Messages\Content\AttachmentContent;
use AsmaaGamal\MetaMessaging\Messages\Content\TemplateContent;
use AsmaaGamal\MetaMessaging\Messages\Content\TextContent;
use AsmaaGamal\MetaMessaging\Messages\MessageBuilder;
use AsmaaGamal\MetaMessaging\Messages\QuickReply;

/**
 * Enforces Meta's documented size and shape limits.
 *
 * Nested content checks itself — templates validate their cards, cards validate
 * their buttons — so this walks only the top level and lets the composite
 * recurse.
 */
final class ConstraintValidator implements Validator
{
    public function validate(MessageBuilder $message, MessagingChannel $channel): void
    {
        $this->validateContent($message, $channel);
        $this->validateQuickReplies($message);
        $this->validatePrivateReply($message, $channel);
    }

    private function validateContent(MessageBuilder $message, MessagingChannel $channel): void
    {
        $content = $message->content();

        match (true) {
            $content instanceof TextContent => $this->validateText($content, $channel),
            $content instanceof AttachmentContent => $content->validate(),
            $content instanceof TemplateContent => $content->template->validate(),
            default => null,
        };
    }

    private function validateText(TextContent $content, MessagingChannel $channel): void
    {
        $limit = $channel->channel()->textLimit();

        if ($content->length() > $limit) {
            throw MessageValidationException::textTooLong($channel->channel(), $content->length());
        }
    }

    private function validateQuickReplies(MessageBuilder $message): void
    {
        $replies = $message->quickReplyList();

        if (count($replies) > QuickReply::MAX_PER_MESSAGE) {
            throw MessageValidationException::tooManyQuickReplies(
                count($replies),
                QuickReply::MAX_PER_MESSAGE,
            );
        }

        foreach ($replies as $reply) {
            $reply->validate();
        }
    }

    /**
     * Meta accepts a private reply request carrying media, then silently
     * delivers only the text. Failing loudly is far kinder than that.
     */
    private function validatePrivateReply(MessageBuilder $message, MessagingChannel $channel): void
    {
        if (! $message->isPrivateReply()) {
            return;
        }

        $carriesNonText = ! $message->content() instanceof TextContent
            || $message->quickReplyList() !== [];

        if ($carriesNonText) {
            throw MessageValidationException::privateReplyTextOnly($channel->channel());
        }
    }
}
