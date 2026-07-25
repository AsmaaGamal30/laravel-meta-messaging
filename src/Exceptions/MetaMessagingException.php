<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Exceptions;

use AsmaaGamal\MetaMessaging\Enums\Channel;
use AsmaaGamal\MetaMessaging\Responses\MetaError;
use RuntimeException;

/**
 * Base class for every failure this package raises.
 *
 * Catch this to handle anything that went wrong; catch a subclass to handle one
 * specific cause. The structured detail always lives on the wrapped MetaError,
 * so exceptions and failed responses expose exactly the same information.
 */
abstract class MetaMessagingException extends RuntimeException
{
    public function __construct(public readonly MetaError $error)
    {
        parent::__construct($error->summary(), $error->code ?? 0);
    }

    /**
     * The full structured error.
     */
    public function error(): MetaError
    {
        return $this->error;
    }

    /**
     * A stable machine-readable slug, e.g. "window_expired". Safe to switch on.
     */
    public function key(): string
    {
        return $this->error->key;
    }

    /**
     * Plain-English explanation of what to do about this failure.
     */
    public function hint(): string
    {
        return $this->error->hint;
    }

    /**
     * Meta's numeric error code, when the failure came from the API.
     */
    public function apiCode(): ?int
    {
        return $this->error->code;
    }

    /**
     * Meta's error_subcode, which is usually the precise cause.
     */
    public function subcode(): ?int
    {
        return $this->error->subcode;
    }

    /**
     * Meta's fbtrace_id. Quote this when opening a bug report with Meta.
     */
    public function traceId(): ?string
    {
        return $this->error->traceId;
    }

    public function channel(): ?Channel
    {
        return $this->error->channel;
    }

    public function endpoint(): ?string
    {
        return $this->error->endpoint;
    }

    /**
     * Whether retrying the identical request could plausibly succeed.
     */
    public function isRetryable(): bool
    {
        return $this->error->isRetryable();
    }

    /**
     * The request context that produced this failure, with credentials removed.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->error->context;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->error->toArray();
    }
}
