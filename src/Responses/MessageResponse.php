<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Responses;

use AsmaaGamal\MetaMessaging\Enums\Channel;
use JsonSerializable;

/**
 * The structured outcome of a call, successful or not.
 *
 * send() throws on failure by default; sendSafely() always returns one of these
 * with failed() true and error() populated.
 */
final readonly class MessageResponse implements JsonSerializable
{
    /**
     * @param  array<string, mixed>  $raw  Meta's decoded response body
     */
    public function __construct(
        public Channel $channel,
        public bool $successful,
        public array $raw = [],
        public ?MetaError $error = null,
        public ?int $status = null,
    ) {}

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function success(Channel $channel, array $raw, ?int $status = 200): self
    {
        return new self($channel, true, $raw, null, $status);
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function failure(Channel $channel, MetaError $error, array $raw = [], ?int $status = null): self
    {
        return new self($channel, false, $raw, $error, $status);
    }

    public function failed(): bool
    {
        return ! $this->successful;
    }

    /**
     * The ID Meta assigned to the message that was sent, when there was one.
     */
    public function messageId(): ?string
    {
        $id = $this->raw['message_id'] ?? $this->raw['id'] ?? null;

        return is_string($id) ? $id : null;
    }

    /**
     * The recipient's scoped ID, echoed back by the Send API.
     */
    public function recipientId(): ?string
    {
        $id = $this->raw['recipient_id'] ?? null;

        return is_string($id) ? $id : null;
    }

    /**
     * The reusable attachment ID returned by an attachment upload.
     */
    public function attachmentId(): ?string
    {
        $id = $this->raw['attachment_id'] ?? null;

        return is_string($id) ? $id : null;
    }

    /**
     * The error, if this response failed.
     */
    public function error(): ?MetaError
    {
        return $this->error;
    }

    /**
     * Read an arbitrary key from Meta's raw response.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->raw[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'successful' => $this->successful,
            'channel' => $this->channel->value,
            'status' => $this->status,
            'message_id' => $this->messageId(),
            'recipient_id' => $this->recipientId(),
            'error' => $this->error?->toArray(),
            'raw' => $this->raw,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
