<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Enums;

/**
 * How aggressively the recipient's device should announce the message.
 */
enum NotificationType: string
{
    /** Sound or vibration plus a push notification. */
    case Regular = 'REGULAR';

    /** Push notification only, no sound. */
    case SilentPush = 'SILENT_PUSH';

    /** No push notification at all. */
    case NoPush = 'NO_PUSH';
}
