<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Exceptions;

/**
 * Maps Meta's error codes onto typed exceptions and hint keys.
 *
 * Deliberately a plain data table rather than a chain of conditionals: Meta adds
 * and retires codes without notice, so recognising a new one should be a single
 * array entry — or a call to extend() from an application, with no package
 * release needed at all.
 *
 * Codes that match nothing degrade to MetaApiException carrying Meta's own
 * description, so an unmapped failure is never silently swallowed.
 */
final class ErrorCatalog
{
    /**
     * Rules added at runtime by the application. Checked before the built-ins.
     *
     * @var array<int, array<string, mixed>>
     */
    private static array $custom = [];

    /**
     * Register an extra rule, or override a built-in one.
     *
     * @param  int|null  $code  Meta's error code
     * @param  int|null  $subcode  Meta's error_subcode, when it narrows the cause
     * @param  class-string<MetaMessagingException>  $exception
     * @param  string  $key  hint key resolved from lang/en/errors.php
     * @param  string|null  $matches  optional regex tested against Meta's message
     */
    public static function extend(
        ?int $code,
        string $exception,
        string $key,
        ?int $subcode = null,
        bool $retryable = false,
        ?string $matches = null,
    ): void {
        self::$custom[] = [
            'code' => $code,
            'subcode' => $subcode,
            'matches' => $matches,
            'exception' => $exception,
            'key' => $key,
            'retryable' => $retryable,
        ];
    }

    /**
     * Drop every runtime rule. Useful between tests.
     */
    public static function flush(): void
    {
        self::$custom = [];
    }

    /**
     * Find the best rule for a Meta error.
     *
     * More specific rules win: an exact code and subcode pair beats a message
     * pattern, which beats a bare code.
     *
     * @return array{exception: class-string<MetaMessagingException>, key: string, retryable: bool}
     */
    public static function resolve(?int $code, ?int $subcode, string $message): array
    {
        $best = null;
        $bestScore = -1;

        foreach ([...self::$custom, ...self::rules()] as $rule) {
            $score = self::score($rule, $code, $subcode, $message);

            if ($score > $bestScore) {
                $best = $rule;
                $bestScore = $score;
            }
        }

        if ($best === null || $bestScore < 0) {
            return [
                'exception' => MetaApiException::class,
                'key' => 'unknown_api_error',
                'retryable' => false,
            ];
        }

        return [
            'exception' => $best['exception'],
            'key' => $best['key'],
            'retryable' => (bool) $best['retryable'],
        ];
    }

    /**
     * How well a rule fits, or -1 when it does not apply at all.
     *
     * The weights encode how much each kind of match narrows the cause. A
     * subcode is the most precise signal Meta gives. A message pattern comes
     * next, because Meta reuses broad codes — 100 covers everything from a
     * deleted comment to a malformed template — so matching the wording is more
     * telling than matching the number. A bare code is the weakest signal and
     * acts as the fallback for its family.
     *
     * @param  array<string, mixed>  $rule
     */
    private static function score(array $rule, ?int $code, ?int $subcode, string $message): int
    {
        $score = 0;

        if ($rule['code'] !== null) {
            if ($rule['code'] !== $code) {
                return -1;
            }
            $score += 4;
        }

        if ($rule['subcode'] !== null) {
            if ($rule['subcode'] !== $subcode) {
                return -1;
            }
            $score += 8;
        }

        if ($rule['matches'] !== null) {
            if (preg_match($rule['matches'], $message) !== 1) {
                return -1;
            }
            $score += 6;
        }

        return $score > 0 ? $score : -1;
    }

    /**
     * The built-in mapping table.
     *
     * Sources: Meta's Messenger Platform and Graph API error references, plus
     * codes observed in the wild. Anything not listed still surfaces cleanly as
     * a MetaApiException with Meta's own text.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function rules(): array
    {
        return [
            // --- Messaging window ---------------------------------------------
            self::rule(10, MessagingWindowExpiredException::class, 'window_expired', subcode: 2018278),
            self::rule(10, MessagingWindowExpiredException::class, 'window_expired', matches: '/outside.*allowed window|24[- ]hour/i'),
            self::rule(10, MessagingWindowExpiredException::class, 'daily_limit_reached', subcode: 1893016),
            self::rule(10, PermissionDeniedException::class, 'permission_denied', subcode: 2018065),
            self::rule(10, RecipientUnavailableException::class, 'recipient_unavailable', subcode: 2018108),
            self::rule(10, PermissionDeniedException::class, 'app_in_development', subcode: 2018028),
            self::rule(10, MessagingWindowExpiredException::class, 'window_expired'),

            // --- Recipient reachability ---------------------------------------
            self::rule(551, RecipientUnavailableException::class, 'recipient_unavailable'),
            self::rule(1545041, RecipientUnavailableException::class, 'recipient_unavailable'),
            self::rule(2018001, RecipientNotFoundException::class, 'recipient_not_found'),

            // --- Comments, private replies ------------------------------------
            self::rule(100, InvalidCommentException::class, 'invalid_comment', subcode: 2018292),
            self::rule(100, InvalidCommentException::class, 'invalid_comment', matches: '/invalid comment[_ ]?id/i'),
            self::rule(null, PrivateReplyAlreadySentException::class, 'private_reply_already_sent', matches: '/already (been )?(sent|replied)/i'),
            self::rule(2534037, PrivateReplyAlreadySentException::class, 'private_reply_already_sent'),
            self::rule(null, PrivateReplyNotAllowedException::class, 'private_reply_not_allowed', matches: '/respond in private|private repl(y|ies) (is|are) not|cannot.*private/i'),
            self::rule(2018334, PrivateReplyNotAllowedException::class, 'private_reply_not_allowed'),

            // --- Access tokens -------------------------------------------------
            self::rule(190, InvalidAccessTokenException::class, 'token_revoked', subcode: 458),
            self::rule(190, InvalidAccessTokenException::class, 'token_password_changed', subcode: 460),
            self::rule(190, InvalidAccessTokenException::class, 'token_expired', subcode: 463),
            self::rule(190, InvalidAccessTokenException::class, 'token_revoked', subcode: 467),
            self::rule(190, InvalidAccessTokenException::class, 'not_page_admin', subcode: 492),
            self::rule(190, InvalidAccessTokenException::class, 'token_invalid'),
            self::rule(102, InvalidAccessTokenException::class, 'token_expired'),
            self::rule(2500, InvalidAccessTokenException::class, 'token_missing'),

            // --- Permissions ---------------------------------------------------
            self::rule(200, PermissionDeniedException::class, 'permission_denied'),
            self::rule(3, PermissionDeniedException::class, 'permission_denied'),
            self::rule(33, PermissionDeniedException::class, 'object_not_found'),
            self::rule(803, PermissionDeniedException::class, 'object_not_found'),

            // --- Throttling ----------------------------------------------------
            self::rule(4, RateLimitExceededException::class, 'rate_limited', retryable: true),
            self::rule(17, RateLimitExceededException::class, 'rate_limited', retryable: true),
            self::rule(32, RateLimitExceededException::class, 'rate_limited', retryable: true),
            self::rule(613, RateLimitExceededException::class, 'rate_limited', retryable: true),
            self::rule(341, TemporarilyBlockedException::class, 'temporarily_blocked', retryable: true),
            self::rule(368, TemporarilyBlockedException::class, 'temporarily_blocked'),

            // --- Attachments ---------------------------------------------------
            self::rule(546, AttachmentException::class, 'attachment_unfetchable'),
            self::rule(2018047, AttachmentException::class, 'attachment_unfetchable'),

            // --- Catch-all for an otherwise bare invalid parameter --------------
            self::rule(100, MetaApiException::class, 'invalid_parameter'),
            self::rule(1, MetaApiException::class, 'unknown_api_error', retryable: true),
            self::rule(2, MetaApiException::class, 'unknown_api_error', retryable: true),
        ];
    }

    /**
     * @param  class-string<MetaMessagingException>  $exception
     * @return array<string, mixed>
     */
    private static function rule(
        ?int $code,
        string $exception,
        string $key,
        ?int $subcode = null,
        bool $retryable = false,
        ?string $matches = null,
    ): array {
        return compact('code', 'subcode', 'matches', 'exception', 'key', 'retryable');
    }
}
