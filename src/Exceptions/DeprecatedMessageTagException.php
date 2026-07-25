<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Exceptions;

use AsmaaGamal\MetaMessaging\Enums\MessageTag;
use AsmaaGamal\MetaMessaging\Responses\MetaError;
use AsmaaGamal\MetaMessaging\Support\Hint;

/**
 * A message tag Meta retired on 27 April 2026 was used.
 *
 * This one is worth its own class. Meta answers such requests with a bare
 * "(#100) Invalid parameter" that never mentions the tag, so the cause is
 * genuinely hard to find from the response alone.
 */
final class DeprecatedMessageTagException extends MetaMessagingException
{
    public static function make(MessageTag $tag): self
    {
        return new self(MetaError::local(
            key: 'deprecated_tag',
            message: sprintf('The %s message tag was retired on %s.', $tag->value, $tag->retiredOn() ?? 'an earlier date'),
            hint: Hint::get('deprecated_tag', [
                'tag' => $tag->value,
                'date' => $tag->retiredOn() ?? 'an earlier date',
                'replacement' => $tag->replacement() ?? 'a supported tag',
            ]),
            context: [
                'tag' => $tag->value,
                'retired_on' => $tag->retiredOn(),
                'supported_tags' => array_map(
                    static fn (MessageTag $supported): string => $supported->value,
                    MessageTag::supported(),
                ),
            ],
        ));
    }
}
