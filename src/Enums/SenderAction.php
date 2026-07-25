<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Enums;

/**
 * Values accepted by the sender_action parameter of the Send API.
 */
enum SenderAction: string
{
    case MarkSeen = 'mark_seen';
    case TypingOn = 'typing_on';
    case TypingOff = 'typing_off';
    case React = 'react';
    case Unreact = 'unreact';
}
