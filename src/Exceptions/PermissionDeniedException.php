<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Exceptions;

/**
 * The token lacks a permission the request needs, or the app is not approved.
 *
 * The hint names the specific scope that is missing.
 */
final class PermissionDeniedException extends MetaMessagingException {}
