<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Channels;

use AsmaaGamal\MetaMessaging\Contracts\Transport;
use AsmaaGamal\MetaMessaging\Enums\AttachmentType;
use AsmaaGamal\MetaMessaging\Enums\Capability;
use AsmaaGamal\MetaMessaging\Enums\Channel;
use AsmaaGamal\MetaMessaging\Events\MetaMessageFailed;
use AsmaaGamal\MetaMessaging\Events\MetaMessageSent;
use AsmaaGamal\MetaMessaging\Exceptions\ErrorMapper;
use AsmaaGamal\MetaMessaging\Exceptions\UnsupportedFeatureException;
use AsmaaGamal\MetaMessaging\Messages\Content\AttachmentContent;
use AsmaaGamal\MetaMessaging\Messages\MessageBuilder;
use AsmaaGamal\MetaMessaging\Responses\MessageResponse;
use AsmaaGamal\MetaMessaging\Support\AccountConfig;
use AsmaaGamal\MetaMessaging\Support\GraphVersion;
use AsmaaGamal\MetaMessaging\Transport\MetaRequest;
use AsmaaGamal\MetaMessaging\Validation\ValidationPipeline;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Base for the two messaging surfaces.
 *
 * Holds everything the surfaces share — building requests, running validation,
 * dispatching, raising errors — and leaves the differences (host, endpoints,
 * feature set) to the subclasses.
 */
abstract class MessagingChannel
{
    public function __construct(
        protected AccountConfig $account,
        protected Transport $transport,
        protected ValidationPipeline $validator,
        protected ?Dispatcher $events = null,
        protected bool $throwOnError = true,
    ) {}

    /**
     * Which surface this is.
     */
    abstract public function channel(): Channel;

    /**
     * What this surface can do.
     */
    abstract public function capabilities(): Capabilities;

    /**
     * The endpoint messages are posted to.
     */
    abstract public function messagesPath(): string;

    /**
     * The endpoint that replies publicly to a comment.
     */
    abstract public function commentReplyPath(string $commentId): string;

    // -----------------------------------------------------------------
    // Configuration
    // -----------------------------------------------------------------

    /**
     * Use a different Graph API version for calls made from here on.
     */
    public function usingVersion(string $version): static
    {
        $clone = clone $this;
        $clone->account = $this->account->withVersion(GraphVersion::make($version));

        return $clone;
    }

    /**
     * Use a token resolved at runtime rather than the configured one.
     *
     * Useful for multi-tenant applications that store a token per customer.
     */
    public function usingToken(string $token, ?string $id = null): static
    {
        $clone = clone $this;
        $clone->account = $this->account->withCredentials($token, $id);

        return $clone;
    }

    /**
     * Return a failed MessageResponse instead of throwing, for calls from here on.
     */
    public function withoutExceptions(): static
    {
        $clone = clone $this;
        $clone->throwOnError = false;

        return $clone;
    }

    public function account(): AccountConfig
    {
        return $this->account;
    }

    // -----------------------------------------------------------------
    // Composing messages
    // -----------------------------------------------------------------

    /**
     * Start a message to a person.
     *
     * @param  string  $id  a page-scoped ID on Messenger, or an Instagram-scoped ID
     */
    public function to(string $id): MessageBuilder
    {
        return (new MessageBuilder($this))->to($id);
    }

    /**
     * Start a message with no recipient set yet.
     */
    public function message(): MessageBuilder
    {
        return new MessageBuilder($this);
    }

    // -----------------------------------------------------------------
    // Comments
    // -----------------------------------------------------------------

    /**
     * Send a private message to whoever left a public comment.
     *
     * Text only — Meta drops any other content. Permitted once per comment,
     * within 7 days of the comment being created.
     */
    public function privateReply(string $commentId, string $text): MessageResponse
    {
        $this->requireCapability(Capability::PrivateReply);

        return (new MessageBuilder($this))
            ->toComment($commentId)
            ->text($text)
            ->send();
    }

    /**
     * Reply publicly, in the comment thread.
     */
    public function replyToComment(string $commentId, string $message): MessageResponse
    {
        $this->requireCapability(Capability::CommentReply);

        return $this->dispatch(new MetaRequest(
            account: $this->account,
            path: $this->commentReplyPath($commentId),
            payload: ['message' => $message],
        ));
    }

    /**
     * Leave a new top-level comment on a post, reel, or media object.
     */
    public function comment(string $objectId, string $message): MessageResponse
    {
        $this->requireCapability(Capability::CommentReply);

        return $this->dispatch(new MetaRequest(
            account: $this->account,
            path: $objectId.'/comments',
            payload: ['message' => $message],
        ));
    }

    // -----------------------------------------------------------------
    // Attachments
    // -----------------------------------------------------------------

    /**
     * Upload an asset once and reuse it by ID.
     *
     * Saves re-fetching the same file on every send, and is the only way to put
     * media into a media template.
     *
     * @param  string  $source  a public URL or a local file path
     */
    public function uploadAttachment(AttachmentType $type, string $source, bool $reusable = true): MessageResponse
    {
        $this->requireCapability(Capability::AttachmentUpload);

        $attachment = AttachmentContent::from($type, $source, $reusable);
        $attachment->validate();

        $files = $attachment->isLocalUpload()
            ? ['filedata' => (string) $attachment->path]
            : [];

        return $this->dispatch(new MetaRequest(
            account: $this->account,
            path: $this->account->id.'/message_attachments',
            payload: ['message' => $attachment->toPayload()],
            files: $files,
        ));
    }

    // -----------------------------------------------------------------
    // Dispatch
    // -----------------------------------------------------------------

    /**
     * Run the request through validation and the transport.
     *
     * Every path in this package funnels through here, so events and error
     * raising behave identically no matter which method was called.
     */
    public function dispatch(MetaRequest $request, bool $throw = true): MessageResponse
    {
        $response = $this->transport->send($request);

        if ($response->successful) {
            $this->events?->dispatch(new MetaMessageSent($request, $response));

            return $response;
        }

        $this->events?->dispatch(new MetaMessageFailed($request, $response));

        if ($throw && $this->throwOnError && $response->error !== null) {
            throw ErrorMapper::toException($response->error);
        }

        return $response;
    }

    /**
     * The validation pipeline this channel runs before sending.
     */
    public function validator(): ValidationPipeline
    {
        return $this->validator;
    }

    /**
     * Refuse a feature this channel does not have.
     *
     * @throws UnsupportedFeatureException
     */
    public function requireCapability(Capability $capability): void
    {
        if ($this->capabilities()->missing($capability)) {
            throw UnsupportedFeatureException::make(
                $this->channel(),
                $capability,
                $this->capabilities()->supportedTemplates(),
            );
        }
    }
}
