<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Responses;

use AsmaaGamal\MetaMessaging\Enums\Channel;
use AsmaaGamal\MetaMessaging\Exceptions\MetaMessagingException;
use AsmaaGamal\MetaMessaging\Support\Redactor;
use JsonSerializable;

/**
 * Everything known about one failure, in one immutable object.
 *
 * The same instance is used whether the failure is raised as an exception or
 * returned inside a failed MessageResponse, so both paths expose identical
 * information.
 */
final readonly class MetaError implements JsonSerializable
{
    /**
     * @param  string  $key  stable machine-readable slug, e.g. "window_expired"
     * @param  string  $message  Meta's own message, or ours for local failures
     * @param  string  $hint  plain-English explanation of how to fix it
     * @param  array<string, mixed>  $context  request context, credentials removed
     * @param  class-string<MetaMessagingException>|null  $exceptionClass
     *                                                                     the exception this error becomes when raised; decided once, where the
     *                                                                     error is built, so a failure with no Meta code still keeps its type
     */
    public function __construct(
        public string $key,
        public string $message,
        public string $hint,
        public ?Channel $channel = null,
        public ?int $code = null,
        public ?int $subcode = null,
        public ?string $type = null,
        public ?string $userTitle = null,
        public ?string $userMessage = null,
        public ?string $traceId = null,
        public ?int $status = null,
        public ?string $endpoint = null,
        public bool $retryable = false,
        public array $context = [],
        public ?string $exceptionClass = null,
    ) {}

    /**
     * A local failure raised before any request was made.
     *
     * @param  array<string, mixed>  $context
     */
    public static function local(
        string $key,
        string $message,
        string $hint,
        ?Channel $channel = null,
        array $context = [],
    ): self {
        return new self(
            key: $key,
            message: $message,
            hint: $hint,
            channel: $channel,
            context: Redactor::scrub($context),
        );
    }

    /**
     * Whether retrying the identical request could plausibly succeed.
     */
    public function isRetryable(): bool
    {
        return $this->retryable;
    }

    /**
     * A one-line description suitable for logs and exception messages.
     */
    public function summary(): string
    {
        $parts = array_filter([
            $this->channel?->label(),
            $this->code === null ? null : '#'.$this->code.($this->subcode !== null ? '/'.$this->subcode : ''),
            $this->message,
        ]);

        return implode(' ', $parts).' — '.$this->hint;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'message' => $this->message,
            'hint' => $this->hint,
            'channel' => $this->channel?->value,
            'code' => $this->code,
            'subcode' => $this->subcode,
            'type' => $this->type,
            'user_title' => $this->userTitle,
            'user_message' => $this->userMessage,
            'trace_id' => $this->traceId,
            'status' => $this->status,
            'endpoint' => $this->endpoint,
            'retryable' => $this->retryable,
            'context' => $this->context,
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
