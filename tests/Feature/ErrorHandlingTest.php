<?php

declare(strict_types=1);

use AsmaaGamal\MetaMessaging\Exceptions\AttachmentException;
use AsmaaGamal\MetaMessaging\Exceptions\ErrorCatalog;
use AsmaaGamal\MetaMessaging\Exceptions\InvalidAccessTokenException;
use AsmaaGamal\MetaMessaging\Exceptions\InvalidCommentException;
use AsmaaGamal\MetaMessaging\Exceptions\MessagingWindowExpiredException;
use AsmaaGamal\MetaMessaging\Exceptions\MetaApiException;
use AsmaaGamal\MetaMessaging\Exceptions\MetaMessagingException;
use AsmaaGamal\MetaMessaging\Exceptions\PermissionDeniedException;
use AsmaaGamal\MetaMessaging\Exceptions\PrivateReplyAlreadySentException;
use AsmaaGamal\MetaMessaging\Exceptions\PrivateReplyNotAllowedException;
use AsmaaGamal\MetaMessaging\Exceptions\RateLimitExceededException;
use AsmaaGamal\MetaMessaging\Exceptions\RecipientNotFoundException;
use AsmaaGamal\MetaMessaging\Exceptions\RecipientUnavailableException;
use AsmaaGamal\MetaMessaging\Exceptions\TemporarilyBlockedException;
use AsmaaGamal\MetaMessaging\Facades\Meta;

/**
 * Every mapped Meta error should reach the caller as its own exception type,
 * carrying a hint that explains the cause rather than repeating Meta's wording.
 */
it('maps Meta error codes onto typed exceptions', function (
    int $code,
    ?int $subcode,
    string $message,
    string $expected,
    string $hintFragment,
): void {
    Meta::fake()->respondWithError($code, $message, $subcode);

    try {
        Meta::facebook()->to('PSID123')->text('Hello')->send();
        $this->fail('Expected '.$expected.' to be thrown.');
    } catch (MetaMessagingException $e) {
        expect($e)->toBeInstanceOf($expected)
            ->and($e->hint())->toContain($hintFragment)
            ->and($e->apiCode())->toBe($code)
            ->and($e->subcode())->toBe($subcode)
            ->and($e->traceId())->toBe('FAKE_TRACE');
    }
})->with([
    'outside 24h window' => [
        10, 2018278, 'This message is sent outside of allowed window.',
        MessagingWindowExpiredException::class, '24 hour messaging window has closed',
    ],
    'window inferred from message' => [
        10, null, 'This message is sent outside of allowed window.',
        MessagingWindowExpiredException::class, '24 hour messaging window has closed',
    ],
    'daily limit' => [
        10, 1893016, 'Daily message limit reached.',
        MessagingWindowExpiredException::class, 'daily message limit',
    ],
    'app in development' => [
        10, 2018028, 'Cannot message users who are not admins.',
        PermissionDeniedException::class, 'development mode',
    ],
    'person unavailable' => [
        551, null, "This person isn't available right now.",
        RecipientUnavailableException::class, 'blocked the',
    ],
    'no matching user' => [
        2018001, null, 'No matching user found.',
        RecipientNotFoundException::class, 'Page-scoped IDs only work',
    ],
    'invalid comment' => [
        100, 2018292, 'Invalid comment_id',
        InvalidCommentException::class, 'invalid, was deleted',
    ],
    'token expired' => [
        190, 463, 'Error validating access token: Session has expired.',
        InvalidAccessTokenException::class, 'has expired',
    ],
    'password changed' => [
        190, 460, 'The session has been invalidated because the user changed their password.',
        InvalidAccessTokenException::class, 'changed their password',
    ],
    'token revoked' => [
        190, 467, 'Access token has been revoked.',
        InvalidAccessTokenException::class, 'was revoked',
    ],
    'not page admin' => [
        190, 492, 'The user must be an administrator of the page.',
        InvalidAccessTokenException::class, 'without an admin role',
    ],
    'permissions' => [
        200, null, 'Permissions error',
        PermissionDeniedException::class, 'pages_messaging',
    ],
    'rate limited' => [
        613, null, 'Calls to this api have exceeded the rate limit.',
        RateLimitExceededException::class, 'rate limiting',
    ],
    'temporarily blocked' => [
        368, null, 'The action attempted has been deemed abusive.',
        TemporarilyBlockedException::class, 'temporarily blocked',
    ],
    'attachment unfetchable' => [
        2018047, null, 'Unable to fetch the file from the URL.',
        AttachmentException::class, 'publicly reachable',
    ],
    'bare invalid parameter' => [
        100, null, '(#100) Invalid parameter',
        MetaApiException::class, 'did not say which one',
    ],
    'unmapped code' => [
        99999, null, 'Something entirely new went wrong.',
        MetaApiException::class, 'no specific mapping',
    ],
]);

it('recognises a private reply refusal from the message text', function (): void {
    Meta::fake()->respondWithError(
        100,
        "This person's settings don't allow others to respond in private to their comment.",
    );

    expect(fn () => Meta::facebook()->privateReply('COMMENT_1', 'Hi'))
        ->toThrow(PrivateReplyNotAllowedException::class);
});

it('recognises a repeated private reply', function (): void {
    Meta::fake()->respondWithError(100, 'A private reply has already been sent for this comment.');

    try {
        Meta::facebook()->privateReply('COMMENT_1', 'Hi');
    } catch (PrivateReplyAlreadySentException $e) {
        expect($e->hint())->toContain('exactly one per comment');
    }
});

it('marks throttling errors retryable and permanent errors not', function (): void {
    Meta::fake()->respondWithError(613, 'Rate limit');

    $retryable = Meta::facebook()->to('P')->text('x')->sendSafely();

    Meta::fake()->respondWithError(551, "This person isn't available right now.");

    $permanent = Meta::facebook()->to('P')->text('x')->sendSafely();

    expect($retryable->error?->isRetryable())->toBeTrue()
        ->and($permanent->error?->isRetryable())->toBeFalse();
});

it('returns a structured failure instead of throwing when asked', function (): void {
    Meta::fake()->respondWithError(551, "This person isn't available right now.");

    $response = Meta::facebook()->to('PSID123')->text('Hello')->sendSafely();

    expect($response->failed())->toBeTrue()
        ->and($response->error?->key)->toBe('recipient_unavailable')
        ->and($response->error?->code)->toBe(551)
        ->and($response->error?->status)->toBe(400)
        ->and($response->toArray())->toHaveKeys(['successful', 'channel', 'error', 'raw']);
});

it('keeps the access token out of the error context', function (): void {
    Meta::fake()->respondWithError(190, 'Invalid OAuth access token.');

    $response = Meta::facebook()->to('PSID123')->text('Hello')->sendSafely();

    expect(json_encode($response->error?->context))->not->toContain('page-token');
});

it('lets an application register its own error code', function (): void {
    ErrorCatalog::extend(
        code: 4242,
        exception: RateLimitExceededException::class,
        key: 'rate_limited',
        retryable: true,
    );

    Meta::fake()->respondWithError(4242, 'A brand new throttling code.');

    expect(fn () => Meta::facebook()->to('P')->text('x')->send())
        ->toThrow(RateLimitExceededException::class);
});

it('exposes the full error as an array for logging', function (): void {
    Meta::fake()->respondWithError(10, 'Outside allowed window', 2018278);

    $response = Meta::facebook()->to('PSID123')->text('Hello')->sendSafely();

    expect($response->error?->toArray())->toHaveKeys([
        'key', 'message', 'hint', 'channel', 'code', 'subcode',
        'trace_id', 'status', 'endpoint', 'retryable', 'context',
    ]);
});
