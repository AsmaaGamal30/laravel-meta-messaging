<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Exceptions;

/**
 * The request never reached Meta, or the response was unreadable.
 *
 * Connection failures, timeouts, and malformed JSON all land here. Retryable.
 */
final class TransportException extends MetaMessagingException {}
