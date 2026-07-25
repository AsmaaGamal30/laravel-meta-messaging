<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Exceptions;

/**
 * Meta could not fetch or accept the attachment.
 *
 * Typically an unreachable URL, an unsupported format, or a file over the size
 * ceiling for its type.
 */
final class AttachmentException extends MetaMessagingException {}
