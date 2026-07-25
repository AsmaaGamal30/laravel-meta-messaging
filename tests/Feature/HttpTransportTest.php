<?php

declare(strict_types=1);

use AsmaaGamal\MetaMessaging\Enums\AttachmentType;
use AsmaaGamal\MetaMessaging\Exceptions\InvalidAccessTokenException;
use AsmaaGamal\MetaMessaging\Exceptions\TransportException;
use AsmaaGamal\MetaMessaging\Facades\Meta;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * These go through the real HttpTransport with Http::fake() underneath, so they
 * verify the bytes actually put on the wire — not just what the builder holds.
 */
it('posts the documented body to the Graph API', function (): void {
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'recipient_id' => 'PSID123',
            'message_id' => 'mid.real',
        ]),
    ]);

    $response = Meta::facebook()->to('PSID123')->text('Hello')->send();

    expect($response->messageId())->toBe('mid.real');

    Http::assertSent(function (Request $request): bool {
        expect($request->url())->toBe('https://graph.facebook.com/v25.0/1010101/messages')
            ->and($request->method())->toBe('POST')
            ->and($request->data())->toBe([
                'recipient' => ['id' => 'PSID123'],
                'message' => ['text' => 'Hello'],
                'access_token' => 'page-token',
            ]);

        return true;
    });
});

it('maps a real Graph API error body to a typed exception', function (): void {
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'error' => [
                'message' => 'Error validating access token: Session has expired.',
                'type' => 'OAuthException',
                'code' => 190,
                'error_subcode' => 463,
                'fbtrace_id' => 'AbCdEf123',
            ],
        ], 400),
    ]);

    try {
        Meta::facebook()->to('PSID123')->text('Hello')->send();
        $this->fail('Expected an InvalidAccessTokenException.');
    } catch (InvalidAccessTokenException $e) {
        expect($e->hint())->toContain('has expired')
            ->and($e->traceId())->toBe('AbCdEf123')
            ->and($e->subcode())->toBe(463);
    }
});

it('passes through Meta user-facing text for an unmapped error', function (): void {
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'error' => [
                'message' => 'Something novel',
                'code' => 987654,
                'error_user_title' => 'Cannot send',
                'error_user_msg' => 'Try again later.',
            ],
        ], 400),
    ]);

    $response = Meta::facebook()->to('PSID')->text('Hi')->sendSafely();

    expect($response->error?->userTitle)->toBe('Cannot send')
        ->and($response->error?->userMessage)->toBe('Try again later.');
});

it('reports a connection failure as a retryable transport error', function (): void {
    Http::fake(fn () => throw new ConnectionException('Connection timed out'));

    try {
        Meta::facebook()->to('PSID')->text('Hi')->send();
        $this->fail('Expected a TransportException.');
    } catch (TransportException $e) {
        expect($e->hint())->toContain('Connection timed out')
            ->and($e->isRetryable())->toBeTrue();
    }
});

it('reports a non-JSON response rather than failing to parse it', function (): void {
    Http::fake(['graph.facebook.com/*' => Http::response('<html>502 Bad Gateway</html>', 502)]);

    $response = Meta::facebook()->to('PSID')->text('Hi')->sendSafely();

    expect($response->error?->key)->toBe('malformed_response')
        ->and($response->error?->isRetryable())->toBeTrue()
        ->and($response->error?->context['body'])->toContain('502 Bad Gateway');
});

it('treats an error object in a 200 response as a failure', function (): void {
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'error' => ['message' => 'Sneaky failure', 'code' => 551],
        ], 200),
    ]);

    $response = Meta::facebook()->to('PSID')->text('Hi')->sendSafely();

    expect($response->failed())->toBeTrue()
        ->and($response->error?->key)->toBe('recipient_unavailable');
});

it('returns the attachment id from an upload', function (): void {
    Http::fake(['graph.facebook.com/*' => Http::response(['attachment_id' => '99887766'])]);

    $response = Meta::facebook()->uploadAttachment(
        AttachmentType::Image,
        'https://example.com/logo.png',
    );

    expect($response->attachmentId())->toBe('99887766');

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/1010101/message_attachments')
        && $request->data()['message']['attachment']['payload']['is_reusable'] === true);
});
