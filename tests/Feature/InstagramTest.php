<?php

declare(strict_types=1);

use AsmaaGamal\MetaMessaging\Enums\AttachmentType;
use AsmaaGamal\MetaMessaging\Exceptions\UnsupportedFeatureException;
use AsmaaGamal\MetaMessaging\Facades\Meta;
use AsmaaGamal\MetaMessaging\Messages\Buttons\PostbackButton;
use AsmaaGamal\MetaMessaging\Messages\Templates\ButtonTemplate;

it('sends Instagram messages to graph.instagram.com under the Instagram Login flow', function (): void {
    $fake = Meta::fake();

    Meta::instagram()->to('IGSID123')->text('Thanks for reaching out')->send();

    expect($fake->lastRequest()?->url())
        ->toBe('https://graph.instagram.com/v25.0/2020202/messages');
});

it('sends to graph.facebook.com under the Facebook Login flow', function (): void {
    config()->set('meta-messaging.accounts.instagram.linked', [
        'account_id' => '3030303',
        'token' => 'page-token',
        'login_type' => 'facebook',
    ]);

    $fake = Meta::fake();

    Meta::instagram('linked')->to('IGSID123')->text('Hi')->send();

    expect($fake->lastRequest()?->url())
        ->toBe('https://graph.facebook.com/v25.0/3030303/messages');
});

it('names the right permission scope per login flow', function (): void {
    config()->set('meta-messaging.accounts.instagram.linked', [
        'account_id' => '3030303',
        'token' => 'page-token',
        'login_type' => 'facebook',
    ]);

    Meta::fake();

    expect(Meta::instagram()->account()->messagingScope())
        ->toBe('instagram_business_manage_messages')
        ->and(Meta::instagram('linked')->account()->messagingScope())
        ->toBe('instagram_manage_messages');
});

it('supports the templates Instagram does have', function (): void {
    $fake = Meta::fake();

    Meta::instagram()->to('IGSID')->template(
        ButtonTemplate::make('Choose')->button(PostbackButton::make('Yes', 'YES'))
    )->send();

    expect($fake->lastRequest()?->payload['message']['attachment']['payload']['template_type'])
        ->toBe('button');
});

it('refuses reusable attachment uploads, which Instagram does not offer', function (): void {
    Meta::fake();

    expect(fn () => Meta::instagram()->uploadAttachment(
        AttachmentType::Image,
        'https://example.com/a.png',
    ))->toThrow(UnsupportedFeatureException::class, 'reusable attachment uploads');
});

it('sends every attachment type Instagram accepts', function (string $method): void {
    $fake = Meta::fake();

    Meta::instagram()->to('IGSID')->{$method}('https://example.com/asset')->send();

    $fake->assertSentCount(1);
})->with(['image', 'audio', 'video', 'file']);

it('replies to an Instagram comment under /replies rather than /comments', function (): void {
    $fake = Meta::fake();

    Meta::instagram()->replyToComment('IG_COMMENT_1', 'Thanks!');

    expect($fake->lastRequest()?->path)->toBe('IG_COMMENT_1/replies')
        ->and($fake->lastRequest()?->payload)->toBe(['message' => 'Thanks!']);
});

it('reacts with the heart Instagram allows', function (): void {
    $fake = Meta::fake();

    Meta::instagram()->to('IGSID')->react('mid.abc', '❤');

    expect($fake->lastRequest()?->payload['payload'])
        ->toBe(['message_id' => 'mid.abc', 'reaction' => '❤']);
});
