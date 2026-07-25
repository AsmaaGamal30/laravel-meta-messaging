<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Channels;

use AsmaaGamal\MetaMessaging\Enums\Capability;
use AsmaaGamal\MetaMessaging\Enums\Channel;

/**
 * Facebook Messenger.
 *
 * The fuller of the two surfaces: every template type, every attachment type,
 * reusable uploads, personas, and message tags.
 */
final class FacebookChannel extends MessagingChannel
{
    public function channel(): Channel
    {
        return Channel::Facebook;
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
            Capability::MediaTemplate,
            Capability::ReceiptTemplate,
            Capability::ProductTemplate,
            Capability::CustomerFeedbackTemplate,

            Capability::QuickReplies,
            Capability::ReplyToMessage,
            Capability::Reactions,
            Capability::TypingIndicator,
            Capability::MarkSeen,

            Capability::PrivateReply,
            Capability::CommentReply,

            Capability::AttachmentUpload,
            Capability::Persona,
            Capability::MessageTags,
            Capability::NotificationType,
        ]);
    }

    public function messagesPath(): string
    {
        return $this->account->id.'/messages';
    }

    /**
     * On Facebook, replying to a comment means creating a nested comment.
     */
    public function commentReplyPath(string $commentId): string
    {
        return $commentId.'/comments';
    }
}
