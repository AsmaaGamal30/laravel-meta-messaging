<?php

declare(strict_types=1);

use AsmaaGamal\MetaMessaging\Enums\MessagingType;
use AsmaaGamal\MetaMessaging\Enums\NotificationType;
use AsmaaGamal\MetaMessaging\Facades\Meta;
use AsmaaGamal\MetaMessaging\Messages\QuickReply;
use AsmaaGamal\MetaMessaging\Transport\MetaRequest;

it('sends a text message to the documented endpoint', function (): void {
    $fake = Meta::fake();

    $response = Meta::facebook()->to('PSID123')->text('Hello there')->send();

    expect($response->successful)->toBeTrue()
        ->and($response->messageId())->toBe('m_fake_1')
        ->and($response->recipientId())->toBe('PSID123');

    $request = $fake->lastRequest();

    expect($request?->url())->toBe('https://graph.facebook.com/v25.0/1010101/messages')
        ->and($request?->payload)->toBe([
            'recipient' => ['id' => 'PSID123'],
            'message' => ['text' => 'Hello there'],
        ]);
});

it('attaches the access token to the body but keeps it out of the safe payload', function (): void {
    $fake = Meta::fake();

    Meta::facebook()->to('PSID123')->text('Hi')->send();

    $request = $fake->lastRequest();

    expect($request?->body())->toHaveKey('access_token', 'page-token')
        ->and($request?->safePayload())->not->toHaveKey('access_token');
});

it('sends each attachment type with the right envelope', function (string $method, string $type): void {
    $fake = Meta::fake();

    Meta::facebook()->to('PSID123')->{$method}('https://example.com/asset')->send();

    expect($fake->lastRequest()?->payload['message'])->toBe([
        'attachment' => [
            'type' => $type,
            'payload' => ['url' => 'https://example.com/asset'],
        ],
    ]);
})->with([
    ['image', 'image'],
    ['audio', 'audio'],
    ['video', 'video'],
    ['file', 'file'],
]);

it('marks an attachment reusable when asked', function (): void {
    $fake = Meta::fake();

    Meta::facebook()->to('PSID123')->image('https://example.com/a.png', reusable: true)->send();

    expect($fake->lastRequest()?->payload['message']['attachment']['payload'])
        ->toBe(['url' => 'https://example.com/a.png', 'is_reusable' => true]);
});

it('reuses an attachment by id when the source is not a url or a file', function (): void {
    $fake = Meta::fake();

    Meta::facebook()->to('PSID123')->image('1234567890')->send();

    expect($fake->lastRequest()?->payload['message']['attachment']['payload'])
        ->toBe(['attachment_id' => '1234567890']);
});

it('threads a reply to a specific message', function (): void {
    $fake = Meta::fake();

    Meta::facebook()->to('PSID123')->text('Sure')->replyTo('mid.abc123')->send();

    expect($fake->lastRequest()?->payload['message']['reply_to'])->toBe(['mid' => 'mid.abc123']);
});

it('sends quick replies', function (): void {
    $fake = Meta::fake();

    Meta::facebook()->to('PSID123')
        ->text('Pick one')
        ->quickReplies([
            QuickReply::text('Yes', 'YES'),
            QuickReply::text('No', 'NO'),
            QuickReply::email(),
        ])
        ->send();

    expect($fake->lastRequest()?->payload['message']['quick_replies'])->toBe([
        ['content_type' => 'text', 'title' => 'Yes', 'payload' => 'YES'],
        ['content_type' => 'text', 'title' => 'No', 'payload' => 'NO'],
        ['content_type' => 'user_email'],
    ]);
});

it('carries messaging type, notification type, persona and metadata', function (): void {
    $fake = Meta::fake();

    Meta::facebook()->to('PSID123')
        ->text('Update')
        ->messagingType(MessagingType::Update)
        ->notificationType(NotificationType::SilentPush)
        ->persona('persona-1')
        ->metadata('order-42')
        ->send();

    $payload = $fake->lastRequest()?->payload;

    expect($payload['messaging_type'])->toBe('UPDATE')
        ->and($payload['notification_type'])->toBe('SILENT_PUSH')
        ->and($payload['persona_id'])->toBe('persona-1')
        ->and($payload['message']['metadata'])->toBe('order-42');
});

it('sends sender actions', function (string $method, string $action): void {
    $fake = Meta::fake();

    Meta::facebook()->to('PSID123')->{$method}();

    expect($fake->lastRequest()?->payload)->toBe([
        'recipient' => ['id' => 'PSID123'],
        'sender_action' => $action,
    ]);
})->with([
    ['typing', 'typing_on'],
    ['typingOff', 'typing_off'],
    ['markSeen', 'mark_seen'],
]);

it('reacts and unreacts to a message', function (): void {
    $fake = Meta::fake();

    Meta::facebook()->to('PSID123')->react('mid.abc', '👍');
    Meta::facebook()->to('PSID123')->unreact('mid.abc');

    $requests = $fake->requests();

    expect($requests[0]->payload)->toBe([
        'recipient' => ['id' => 'PSID123'],
        'sender_action' => 'react',
        'payload' => ['message_id' => 'mid.abc', 'reaction' => '👍'],
    ])->and($requests[1]->payload)->toBe([
        'recipient' => ['id' => 'PSID123'],
        'sender_action' => 'unreact',
        'payload' => ['message_id' => 'mid.abc'],
    ]);
});

it('supports assertions through the facade', function (): void {
    Meta::fake();

    Meta::facebook()->to('PSID123')->text('Hello')->send();

    Meta::assertSent(fn (MetaRequest $request): bool => $request->payload['message']['text'] === 'Hello');
    Meta::assertNotSent(fn (MetaRequest $request): bool => $request->channel()->value === 'instagram');
});
