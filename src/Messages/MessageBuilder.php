<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Messages;

use AsmaaGamal\MetaMessaging\Channels\MessagingChannel;
use AsmaaGamal\MetaMessaging\Contracts\MessageContent;
use AsmaaGamal\MetaMessaging\Contracts\Template;
use AsmaaGamal\MetaMessaging\Enums\AttachmentType;
use AsmaaGamal\MetaMessaging\Enums\Capability;
use AsmaaGamal\MetaMessaging\Enums\MessageTag;
use AsmaaGamal\MetaMessaging\Enums\MessagingType;
use AsmaaGamal\MetaMessaging\Enums\NotificationType;
use AsmaaGamal\MetaMessaging\Enums\SenderAction;
use AsmaaGamal\MetaMessaging\Exceptions\MetaMessagingException;
use AsmaaGamal\MetaMessaging\Jobs\SendMetaMessageJob;
use AsmaaGamal\MetaMessaging\Messages\Content\AttachmentContent;
use AsmaaGamal\MetaMessaging\Messages\Content\TemplateContent;
use AsmaaGamal\MetaMessaging\Messages\Content\TextContent;
use AsmaaGamal\MetaMessaging\Responses\MessageResponse;
use AsmaaGamal\MetaMessaging\Transport\MetaRequest;
use Illuminate\Foundation\Bus\PendingDispatch;

/**
 * Composes one Send API request.
 *
 * Fluent and mutable: each method configures the builder and hands it back, so a
 * message reads as a single expression. The content it holds is immutable, so
 * nothing built here can be changed from underneath it.
 */
final class MessageBuilder
{
    /** @var array<string, string> */
    private array $recipient = [];

    private ?MessageContent $content = null;

    /** @var array<int, QuickReply> */
    private array $quickReplies = [];

    private ?string $replyToMessageId = null;

    private ?MessagingType $messagingType = null;

    private ?MessageTag $tag = null;

    private ?NotificationType $notificationType = null;

    private ?string $personaId = null;

    private ?string $metadata = null;

    public function __construct(private readonly MessagingChannel $channel) {}

    // -----------------------------------------------------------------
    // Recipient
    // -----------------------------------------------------------------

    /**
     * Address the message to a person.
     *
     * @param  string  $id  page-scoped ID (Messenger) or Instagram-scoped ID
     */
    public function to(string $id): self
    {
        $this->recipient = ['id' => $id];

        return $this;
    }

    /**
     * Address the message to whoever wrote a comment — a private reply.
     *
     * Text only. Meta permits one per comment, within 7 days of the comment.
     */
    public function toComment(string $commentId): self
    {
        $this->recipient = ['comment_id' => $commentId];

        return $this;
    }

    /**
     * Address the message to whoever wrote a post.
     */
    public function toPost(string $postId): self
    {
        $this->recipient = ['post_id' => $postId];

        return $this;
    }

    /**
     * Address the message using a user_ref from the checkbox plugin.
     */
    public function toUserRef(string $userRef): self
    {
        $this->recipient = ['user_ref' => $userRef];

        return $this;
    }

    // -----------------------------------------------------------------
    // Content
    // -----------------------------------------------------------------

    public function text(string $text): self
    {
        $this->content = new TextContent($text);

        return $this;
    }

    /**
     * @param  string  $source  a public URL, a local file path, or an attachment ID
     */
    public function image(string $source, bool $reusable = false): self
    {
        return $this->attachment(AttachmentType::Image, $source, $reusable);
    }

    /**
     * @param  string  $source  a public URL, a local file path, or an attachment ID
     */
    public function audio(string $source, bool $reusable = false): self
    {
        return $this->attachment(AttachmentType::Audio, $source, $reusable);
    }

    /**
     * @param  string  $source  a public URL, a local file path, or an attachment ID
     */
    public function video(string $source, bool $reusable = false): self
    {
        return $this->attachment(AttachmentType::Video, $source, $reusable);
    }

    /**
     * @param  string  $source  a public URL, a local file path, or an attachment ID
     */
    public function file(string $source, bool $reusable = false): self
    {
        return $this->attachment(AttachmentType::File, $source, $reusable);
    }

    /**
     * Attach media, letting the source type be inferred.
     */
    public function attachment(AttachmentType $type, string $source, bool $reusable = false): self
    {
        $this->content = AttachmentContent::from($type, $source, $reusable);

        return $this;
    }

    /**
     * Send a structured template.
     */
    public function template(Template $template): self
    {
        $this->content = new TemplateContent($template);

        return $this;
    }

    // -----------------------------------------------------------------
    // Modifiers
    // -----------------------------------------------------------------

    /**
     * @param  array<int, QuickReply>  $replies
     */
    public function quickReplies(array $replies): self
    {
        $this->quickReplies = array_values($replies);

        return $this;
    }

    public function quickReply(QuickReply $reply): self
    {
        $this->quickReplies[] = $reply;

        return $this;
    }

    /**
     * Thread this message as a reply to a specific earlier message.
     *
     * @param  string  $messageId  the "mid" from the messages webhook
     */
    public function replyTo(string $messageId): self
    {
        $this->replyToMessageId = $messageId;

        return $this;
    }

    /**
     * Justify sending outside the 24 hour window.
     *
     * Setting a tag implies messaging_type MESSAGE_TAG unless you set another.
     */
    public function tag(MessageTag $tag): self
    {
        $this->tag = $tag;
        $this->messagingType ??= MessagingType::MessageTag;

        return $this;
    }

    public function messagingType(MessagingType $type): self
    {
        $this->messagingType = $type;

        return $this;
    }

    public function notificationType(NotificationType $type): self
    {
        $this->notificationType = $type;

        return $this;
    }

    /**
     * Send as a configured persona rather than the Page itself.
     */
    public function persona(string $personaId): self
    {
        $this->personaId = $personaId;

        return $this;
    }

    /**
     * An opaque string echoed back on the message_echoes webhook.
     */
    public function metadata(string $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    // -----------------------------------------------------------------
    // Terminal actions
    // -----------------------------------------------------------------

    /**
     * Send the message.
     *
     * @throws MetaMessagingException on failure,
     *                                unless meta-messaging.throw is false
     */
    public function send(): MessageResponse
    {
        $this->channel->validator()->validate($this, $this->channel);

        return $this->channel->dispatch($this->request());
    }

    /**
     * Send without ever throwing.
     *
     * Local validation failures are captured into the response too, so one
     * `if ($response->failed())` covers every way this can go wrong.
     */
    public function sendSafely(): MessageResponse
    {
        try {
            $this->channel->validator()->validate($this, $this->channel);
        } catch (MetaMessagingException $e) {
            return MessageResponse::failure($this->channel->channel(), $e->error());
        }

        return $this->channel->dispatch($this->request(), throw: false);
    }

    /**
     * Queue the send instead of performing it inline.
     *
     * Validation still runs now, so a malformed message fails at the call site
     * rather than silently on a worker.
     */
    public function queue(): PendingDispatch
    {
        $this->channel->validator()->validate($this, $this->channel);

        return SendMetaMessageJob::dispatch($this->request());
    }

    /**
     * React to a message in this conversation.
     *
     * @param  string  $messageId  the "mid" of the message being reacted to
     */
    public function react(string $messageId, string $reaction = 'love'): MessageResponse
    {
        $this->channel->requireCapability(Capability::Reactions);
        $this->channel->validator()->validateReaction($reaction, $this->channel);

        return $this->senderAction(SenderAction::React, [
            'message_id' => $messageId,
            'reaction' => $reaction,
        ]);
    }

    /**
     * Remove a reaction previously left on a message.
     */
    public function unreact(string $messageId): MessageResponse
    {
        $this->channel->requireCapability(Capability::Reactions);

        return $this->senderAction(SenderAction::Unreact, ['message_id' => $messageId]);
    }

    /**
     * Show the typing indicator.
     */
    public function typing(): MessageResponse
    {
        $this->channel->requireCapability(Capability::TypingIndicator);

        return $this->senderAction(SenderAction::TypingOn);
    }

    /**
     * Hide the typing indicator.
     */
    public function typingOff(): MessageResponse
    {
        $this->channel->requireCapability(Capability::TypingIndicator);

        return $this->senderAction(SenderAction::TypingOff);
    }

    /**
     * Mark the conversation as read.
     */
    public function markSeen(): MessageResponse
    {
        $this->channel->requireCapability(Capability::MarkSeen);

        return $this->senderAction(SenderAction::MarkSeen);
    }

    // -----------------------------------------------------------------
    // Inspection — used by the validators, and handy in tests
    // -----------------------------------------------------------------

    /**
     * The request that send() would make, without making it.
     */
    public function request(): MetaRequest
    {
        return new MetaRequest(
            account: $this->channel->account(),
            path: $this->channel->messagesPath(),
            payload: $this->toPayload(),
            files: $this->files(),
        );
    }

    /**
     * The full request body.
     *
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        $payload = ['recipient' => $this->recipient];

        if ($this->messagingType !== null) {
            $payload['messaging_type'] = $this->messagingType->value;
        }

        if ($this->content !== null) {
            $payload['message'] = $this->messagePayload();
        }

        if ($this->tag !== null) {
            $payload['tag'] = $this->tag->value;
        }

        if ($this->notificationType !== null) {
            $payload['notification_type'] = $this->notificationType->value;
        }

        if ($this->personaId !== null) {
            $payload['persona_id'] = $this->personaId;
        }

        return $payload;
    }

    /** @return array<string, string> */
    public function recipient(): array
    {
        return $this->recipient;
    }

    public function content(): ?MessageContent
    {
        return $this->content;
    }

    /** @return array<int, QuickReply> */
    public function quickReplyList(): array
    {
        return $this->quickReplies;
    }

    public function replyToMessageId(): ?string
    {
        return $this->replyToMessageId;
    }

    public function messageTag(): ?MessageTag
    {
        return $this->tag;
    }

    public function isPrivateReply(): bool
    {
        return isset($this->recipient['comment_id']) || isset($this->recipient['post_id']);
    }

    // -----------------------------------------------------------------
    // Internals
    // -----------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    private function messagePayload(): array
    {
        $message = $this->content?->toPayload() ?? [];

        if ($this->quickReplies !== []) {
            $message['quick_replies'] = array_map(
                static fn (QuickReply $reply): array => $reply->toPayload(),
                $this->quickReplies,
            );
        }

        if ($this->replyToMessageId !== null) {
            $message['reply_to'] = ['mid' => $this->replyToMessageId];
        }

        if ($this->metadata !== null) {
            $message['metadata'] = $this->metadata;
        }

        return $message;
    }

    /**
     * Local files that must travel as multipart rather than in the JSON body.
     *
     * @return array<string, string>
     */
    private function files(): array
    {
        return $this->content instanceof AttachmentContent && $this->content->isLocalUpload()
            ? ['filedata' => (string) $this->content->path]
            : [];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function senderAction(SenderAction $action, array $payload = []): MessageResponse
    {
        $body = [
            'recipient' => $this->recipient,
            'sender_action' => $action->value,
        ];

        if ($payload !== []) {
            $body['payload'] = $payload;
        }

        return $this->channel->dispatch(new MetaRequest(
            account: $this->channel->account(),
            path: $this->channel->messagesPath(),
            payload: $body,
        ));
    }
}
