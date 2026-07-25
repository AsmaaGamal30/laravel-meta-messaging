<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Exceptions;

/**
 * The recipient cannot receive this message.
 *
 * Covers a blocked Page, a deleted conversation, and privacy settings that shut
 * out third-party apps. Nothing about the request can be changed to fix it.
 */
final class RecipientUnavailableException extends MetaMessagingException {}
