<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Exceptions;

/**
 * A private reply was already sent for this comment.
 *
 * Meta permits exactly one per comment. The thread can only continue if the
 * person answers.
 */
final class PrivateReplyAlreadySentException extends MetaMessagingException {}
