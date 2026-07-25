<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Enums;

/**
 * Message tags justify sending outside the standard 24 hour window.
 *
 * Meta retired most of them on 27 April 2026. Requests carrying a retired tag
 * now come back as a bare "(#100) Invalid parameter", which gives no hint that
 * the tag is the problem, so this package rejects them before the request is
 * made. See DeprecatedTagValidator.
 */
enum MessageTag: string
{
    /** A human agent responding to a person's question. Valid for 7 days. */
    case HumanAgent = 'HUMAN_AGENT';

    /** Requesting feedback after an interaction. */
    case CustomerFeedback = 'CUSTOMER_FEEDBACK';

    /** @deprecated Retired by Meta on 27 April 2026. */
    case ConfirmedEventUpdate = 'CONFIRMED_EVENT_UPDATE';

    /** @deprecated Retired by Meta on 27 April 2026. */
    case AccountUpdate = 'ACCOUNT_UPDATE';

    /** @deprecated Retired by Meta on 27 April 2026. */
    case PostPurchaseUpdate = 'POST_PURCHASE_UPDATE';

    /**
     * Whether Meta has retired this tag.
     */
    public function isDeprecated(): bool
    {
        return in_array($this, self::deprecated(), true);
    }

    /**
     * Every tag Meta has retired.
     *
     * @return array<int, self>
     */
    public static function deprecated(): array
    {
        return [
            self::ConfirmedEventUpdate,
            self::AccountUpdate,
            self::PostPurchaseUpdate,
        ];
    }

    /**
     * Every tag still accepted by the Send API.
     *
     * @return array<int, self>
     */
    public static function supported(): array
    {
        return [
            self::HumanAgent,
            self::CustomerFeedback,
        ];
    }

    /**
     * The date Meta stopped accepting this tag, if it has been retired.
     */
    public function retiredOn(): ?string
    {
        return $this->isDeprecated() ? '2026-04-27' : null;
    }

    /**
     * What to use instead of a retired tag.
     */
    public function replacement(): ?string
    {
        return match ($this) {
            self::ConfirmedEventUpdate,
            self::AccountUpdate,
            self::PostPurchaseUpdate => 'a Utility Template via the Marketing Messages API, or the HUMAN_AGENT tag if a person is replying',
            default => null,
        };
    }

    /**
     * How long after the person's last message this tag remains valid.
     */
    public function windowDescription(): ?string
    {
        return match ($this) {
            self::HumanAgent => '7 days from the person\'s last message',
            self::CustomerFeedback => '7 days from the person\'s last message',
            default => null,
        };
    }
}
