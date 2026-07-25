<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Exceptions;

/**
 * The access token is missing, malformed, expired, or revoked.
 *
 * The wrapped error's subcode distinguishes the cause, and the hint names it.
 */
final class InvalidAccessTokenException extends MetaMessagingException {}
