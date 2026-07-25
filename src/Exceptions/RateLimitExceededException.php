<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Exceptions;

/**
 * An app, page, or endpoint rate limit was hit. Always retryable.
 */
final class RateLimitExceededException extends MetaMessagingException {}
