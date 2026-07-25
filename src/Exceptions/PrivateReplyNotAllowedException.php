<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Exceptions;

/**
 * This comment cannot receive a private reply.
 *
 * The commenter's privacy settings, a Page that has messaging switched off, or
 * a comment left by the Page itself will all trigger this.
 */
final class PrivateReplyNotAllowedException extends MetaMessagingException {}
