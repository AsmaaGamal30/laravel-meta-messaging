<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Exceptions;

/**
 * A failure returned by the Graph API that has no more specific mapping.
 *
 * Meta's own error_user_title and error_user_msg are passed through verbatim on
 * the wrapped MetaError so nothing is lost when the catalog has no entry.
 */
final class MetaApiException extends MetaMessagingException {}
