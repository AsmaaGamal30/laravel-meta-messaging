<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Exceptions;

/**
 * No user matches the supplied recipient ID.
 *
 * Usually a page-scoped ID being sent to the wrong Page, or an Instagram-scoped
 * ID used against Messenger.
 */
final class RecipientNotFoundException extends MetaMessagingException {}
