<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Exceptions;

use AsmaaGamal\MetaMessaging\Enums\AttachmentType;
use AsmaaGamal\MetaMessaging\Enums\Channel;
use AsmaaGamal\MetaMessaging\Responses\MetaError;
use AsmaaGamal\MetaMessaging\Support\Hint;

/**
 * The message breaks a documented Meta limit and would be rejected.
 *
 * Every one of these is caught locally, so a malformed message never costs an
 * API call or counts against a rate limit.
 */
final class MessageValidationException extends MetaMessagingException
{
    /**
     * Build any validation failure from a hint key.
     *
     * @param  array<string, string|int>  $replace
     * @param  array<string, mixed>  $context
     */
    public static function make(
        string $key,
        string $message,
        array $replace = [],
        ?Channel $channel = null,
        array $context = [],
    ): self {
        return new self(MetaError::local(
            key: $key,
            message: $message,
            hint: Hint::get($key, $replace),
            channel: $channel,
            context: $context,
        ));
    }

    public static function emptyMessage(Channel $channel): self
    {
        return self::make('empty_message', 'The message has no content.', channel: $channel);
    }

    public static function missingRecipient(Channel $channel): self
    {
        return self::make('missing_recipient', 'No recipient was set.', channel: $channel);
    }

    public static function textTooLong(Channel $channel, int $length): self
    {
        return self::make(
            key: 'text_too_long',
            message: sprintf('Text is %d characters; %s allows %d.', $length, $channel->label(), $channel->textLimit()),
            replace: ['length' => $length, 'channel' => $channel->label(), 'limit' => $channel->textLimit()],
            channel: $channel,
            context: ['length' => $length, 'limit' => $channel->textLimit()],
        );
    }

    public static function tooManyCards(int $count, int $limit): self
    {
        return self::make(
            key: 'too_many_cards',
            message: sprintf('Generic template has %d cards; the limit is %d.', $count, $limit),
            replace: ['count' => $count, 'limit' => $limit],
            context: ['count' => $count, 'limit' => $limit],
        );
    }

    public static function tooManyButtons(string $context, int $count, int $limit): self
    {
        return self::make(
            key: 'too_many_buttons',
            message: sprintf('%s has %d buttons; the limit is %d.', ucfirst($context), $count, $limit),
            replace: ['context' => $context, 'count' => $count, 'limit' => $limit],
            context: ['count' => $count, 'limit' => $limit],
        );
    }

    public static function tooManyQuickReplies(int $count, int $limit): self
    {
        return self::make(
            key: 'too_many_quick_replies',
            message: sprintf('Message has %d quick replies; the limit is %d.', $count, $limit),
            replace: ['count' => $count, 'limit' => $limit],
            context: ['count' => $count, 'limit' => $limit],
        );
    }

    public static function titleTooLong(string $key, string $title, int $limit): self
    {
        return self::make(
            key: $key,
            message: sprintf('Title "%s" exceeds %d characters.', $title, $limit),
            replace: ['title' => $title, 'length' => mb_strlen($title), 'limit' => $limit],
            context: ['title' => $title, 'limit' => $limit],
        );
    }

    public static function attachmentSourceRequired(): self
    {
        return self::make('attachment_source_required', 'The attachment has no source.');
    }

    public static function attachmentTooLarge(AttachmentType $type, int $bytes): self
    {
        return self::make(
            key: 'attachment_too_large',
            message: sprintf('%s attachment is %d bytes; the limit is %s.', ucfirst($type->value), $bytes, $type->maxSizeLabel()),
            replace: [
                'type' => $type->value,
                'size' => self::humanBytes($bytes),
                'limit' => $type->maxSizeLabel(),
            ],
            context: ['type' => $type->value, 'bytes' => $bytes, 'max_bytes' => $type->maxBytes()],
        );
    }

    public static function localFileUnreadable(string $path): self
    {
        return self::make(
            key: 'local_file_unreadable',
            message: sprintf('Cannot read file [%s].', $path),
            replace: ['path' => $path],
            context: ['path' => $path],
        );
    }

    public static function invalidUrl(string $url): self
    {
        return self::make(
            key: 'invalid_url',
            message: sprintf('Invalid attachment URL [%s].', $url),
            replace: ['url' => $url],
            context: ['url' => $url],
        );
    }

    public static function privateReplyTextOnly(Channel $channel): self
    {
        return self::make(
            key: 'private_reply_text_only',
            message: 'Private replies support text only.',
            channel: $channel,
        );
    }

    public static function instagramReactionUnsupported(string $emoji): self
    {
        return self::make(
            key: 'instagram_reaction_unsupported',
            message: sprintf('Instagram does not accept the [%s] reaction.', $emoji),
            replace: ['emoji' => $emoji],
            channel: Channel::Instagram,
            context: ['emoji' => $emoji],
        );
    }

    public static function tagRequiresMessagingType(): self
    {
        return self::make('tag_requires_messaging_type', 'A message tag requires messaging_type MESSAGE_TAG.');
    }

    private static function humanBytes(int $bytes): string
    {
        return $bytes >= 1048576
            ? round($bytes / 1048576, 1).'MB'
            : round($bytes / 1024).'KB';
    }
}
