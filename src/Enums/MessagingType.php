<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Enums;

/**
 * The messaging_type parameter, which tells Meta why you are allowed to send.
 */
enum MessagingType: string
{
    /** A reply to something the person sent, inside the 24 hour window. */
    case Response = 'RESPONSE';

    /** Proactive, still inside the 24 hour window. */
    case Update = 'UPDATE';

    /** Outside the window, justified by an accompanying message tag. */
    case MessageTag = 'MESSAGE_TAG';
}
