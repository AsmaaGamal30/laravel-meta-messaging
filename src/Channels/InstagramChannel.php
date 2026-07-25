<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Channels;

use AsmaaGamal\MetaMessaging\Enums\Capability;
use AsmaaGamal\MetaMessaging\Enums\Channel;

/**
 * Instagram Direct.
 *
 * Narrower than Messenger. Notably absent: media, receipt, and customer feedback
 * templates; reusable attachment uploads; personas; and message tags. Outbound
 * reactions accept only the heart.
 */
final class InstagramChannel extends MessagingChannel
{
    public function channel(): Channel
    {
        return Channel::Instagram;
    }

    public function capabilities(): Capabilities
    {
        return new Capabilities([
            Capability::Text,
            Capability::ImageAttachment,
            Capability::AudioAttachment,
            Capability::VideoAttachment,
            Capability::FileAttachment,

            Capability::GenericTemplate,
            Capability::ButtonTemplate,
            Capability::ProductTemplate,

            Capability::QuickReplies,
            Capability::ReplyToMessage,
            Capability::Reactions,
            Capability::TypingIndicator,
            Capability::MarkSeen,

            Capability::PrivateReply,
            Capability::CommentReply,
        ]);
    }

    public function messagesPath(): string
    {
        return $this->account->id.'/messages';
    }

    /**
     * Instagram nests comment replies under /replies rather than /comments.
     */
    public function commentReplyPath(string $commentId): string
    {
        return $commentId.'/replies';
    }
}
