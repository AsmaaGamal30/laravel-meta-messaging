<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Enums;

use AsmaaGamal\MetaMessaging\Channels\Capabilities;

/**
 * Individually toggleable features, used to describe what each channel can do.
 *
 * Facebook Messenger and Instagram share a request shape but not a feature set.
 * Rather than scattering "if instagram" checks through the builder, each channel
 * declares the capabilities it holds and CapabilityValidator does the rest.
 *
 * @see Capabilities
 */
enum Capability: string
{
    case Text = 'text';
    case ImageAttachment = 'image_attachment';
    case AudioAttachment = 'audio_attachment';
    case VideoAttachment = 'video_attachment';
    case FileAttachment = 'file_attachment';

    case GenericTemplate = 'generic_template';
    case ButtonTemplate = 'button_template';
    case MediaTemplate = 'media_template';
    case ReceiptTemplate = 'receipt_template';
    case ProductTemplate = 'product_template';
    case CustomerFeedbackTemplate = 'customer_feedback_template';

    case QuickReplies = 'quick_replies';
    case ReplyToMessage = 'reply_to_message';
    case Reactions = 'reactions';
    case TypingIndicator = 'typing_indicator';
    case MarkSeen = 'mark_seen';

    case PrivateReply = 'private_reply';
    case CommentReply = 'comment_reply';

    case AttachmentUpload = 'attachment_upload';
    case Persona = 'persona';
    case MessageTags = 'message_tags';
    case NotificationType = 'notification_type';

    /**
     * A phrase describing the feature, used in "X does not support Y" errors.
     */
    public function label(): string
    {
        return match ($this) {
            self::Text => 'text messages',
            self::ImageAttachment => 'image attachments',
            self::AudioAttachment => 'audio attachments',
            self::VideoAttachment => 'video attachments',
            self::FileAttachment => 'file attachments',
            self::GenericTemplate => 'generic templates',
            self::ButtonTemplate => 'button templates',
            self::MediaTemplate => 'media templates',
            self::ReceiptTemplate => 'receipt templates',
            self::ProductTemplate => 'product templates',
            self::CustomerFeedbackTemplate => 'customer feedback templates',
            self::QuickReplies => 'quick replies',
            self::ReplyToMessage => 'replying to a specific message',
            self::Reactions => 'message reactions',
            self::TypingIndicator => 'typing indicators',
            self::MarkSeen => 'marking messages as seen',
            self::PrivateReply => 'private replies to comments',
            self::CommentReply => 'public comment replies',
            self::AttachmentUpload => 'reusable attachment uploads',
            self::Persona => 'personas',
            self::MessageTags => 'message tags',
            self::NotificationType => 'notification types',
        };
    }
}
