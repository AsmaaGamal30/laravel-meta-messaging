<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Exceptions;

/**
 * The 24 hour messaging window has closed.
 *
 * Meta only lets you message someone within 24 hours of their last message to
 * you. Outside that, the request needs a still-supported message tag.
 */
final class MessagingWindowExpiredException extends MetaMessagingException {}
