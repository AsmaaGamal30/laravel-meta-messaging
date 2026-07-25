<?php

declare(strict_types=1);

use AsmaaGamal\MetaMessaging\Enums\MessageTag;
use AsmaaGamal\MetaMessaging\Exceptions\DeprecatedMessageTagException;
use AsmaaGamal\MetaMessaging\Exceptions\MessageValidationException;
use AsmaaGamal\MetaMessaging\Exceptions\UnsupportedFeatureException;
use AsmaaGamal\MetaMessaging\Facades\Meta;
use AsmaaGamal\MetaMessaging\Messages\Buttons\PostbackButton;
use AsmaaGamal\MetaMessaging\Messages\Buttons\UrlButton;
use AsmaaGamal\MetaMessaging\Messages\QuickReply;
use AsmaaGamal\MetaMessaging\Messages\Templates\Card;
use AsmaaGamal\MetaMessaging\Messages\Templates\GenericTemplate;
use AsmaaGamal\MetaMessaging\Messages\Templates\ReceiptSummary;
use AsmaaGamal\MetaMessaging\Messages\Templates\ReceiptTemplate;

/**
 * Everything here must be caught before a request leaves the process. The
 * assertNothingSent() call in each case is the point of the whole layer.
 */
it('refuses a message with no recipient', function (): void {
    $fake = Meta::fake();

    expect(fn () => Meta::facebook()->message()->text('Hello')->send())
        ->toThrow(MessageValidationException::class, 'No recipient was set');

    $fake->assertNothingSent();
});

it('refuses a message with no content', function (): void {
    $fake = Meta::fake();

    expect(fn () => Meta::facebook()->to('PSID')->send())
        ->toThrow(MessageValidationException::class);

    $fake->assertNothingSent();
});

it('refuses text past the channel limit and names the limit', function (): void {
    $fake = Meta::fake();

    try {
        Meta::instagram()->to('IGSID')->text(str_repeat('a', 1001))->send();
    } catch (MessageValidationException $e) {
        expect($e->hint())->toContain('1001 characters')
            ->and($e->hint())->toContain('at most 1000');
    }

    $fake->assertNothingSent();
});

it('allows Facebook the larger text limit Instagram does not have', function (): void {
    $fake = Meta::fake();

    Meta::facebook()->to('PSID')->text(str_repeat('a', 1500))->send();

    $fake->assertSentCount(1);
});

it('refuses a retired message tag before spending an API call', function (): void {
    $fake = Meta::fake();

    try {
        Meta::facebook()->to('PSID')->text('Your order shipped')->tag(MessageTag::AccountUpdate)->send();
        $this->fail('Expected the retired tag to be refused.');
    } catch (DeprecatedMessageTagException $e) {
        expect($e->hint())->toContain('ACCOUNT_UPDATE')
            ->and($e->hint())->toContain('2026-04-27')
            ->and($e->hint())->toContain('HUMAN_AGENT')
            ->and($e->context()['supported_tags'])->toBe(['HUMAN_AGENT', 'CUSTOMER_FEEDBACK']);
    }

    $fake->assertNothingSent();
});

it('still allows the tags Meta kept', function (): void {
    $fake = Meta::fake();

    Meta::facebook()->to('PSID')->text('Following up')->tag(MessageTag::HumanAgent)->send();

    expect($fake->lastRequest()?->payload)
        ->toHaveKey('tag', 'HUMAN_AGENT')
        ->toHaveKey('messaging_type', 'MESSAGE_TAG');
});

it('refuses more quick replies than Meta accepts', function (): void {
    $fake = Meta::fake();

    $replies = array_map(
        fn (int $i): QuickReply => QuickReply::text('Option '.$i, 'OPT_'.$i),
        range(1, 14),
    );

    expect(fn () => Meta::facebook()->to('PSID')->text('Pick')->quickReplies($replies)->send())
        ->toThrow(MessageValidationException::class, 'at most 13 quick replies');

    $fake->assertNothingSent();
});

it('refuses a quick reply title past 20 characters', function (): void {
    Meta::fake();

    expect(fn () => Meta::facebook()->to('PSID')->text('Pick')
        ->quickReply(QuickReply::text('This title is far too long', 'X'))->send())
        ->toThrow(MessageValidationException::class, 'the limit is 20');
});

it('refuses more than ten cards in a carousel', function (): void {
    $fake = Meta::fake();

    $template = GenericTemplate::make()->cards(
        array_map(fn (int $i): Card => Card::make('Card '.$i), range(1, 11)),
    );

    expect(fn () => Meta::facebook()->to('PSID')->template($template)->send())
        ->toThrow(MessageValidationException::class, 'at most 10 cards');

    $fake->assertNothingSent();
});

it('refuses more than three buttons on a card', function (): void {
    $fake = Meta::fake();

    $card = Card::make('Product')
        ->button(PostbackButton::make('One', 'A'))
        ->button(PostbackButton::make('Two', 'B'))
        ->button(PostbackButton::make('Three', 'C'))
        ->button(PostbackButton::make('Four', 'D'));

    expect(fn () => Meta::facebook()->to('PSID')->template(GenericTemplate::make()->card($card))->send())
        ->toThrow(MessageValidationException::class, 'at most 3 buttons');

    $fake->assertNothingSent();
});

it('refuses a malformed attachment url', function (): void {
    $fake = Meta::fake();

    expect(fn () => Meta::facebook()->to('PSID')->image('https://example.com/ok')->send())
        ->not->toThrow(MessageValidationException::class);

    expect(fn () => Meta::facebook()->to('PSID')->template(
        GenericTemplate::make()->card(Card::make('X')->button(UrlButton::make('Go', 'not a url')))
    )->send())->toThrow(MessageValidationException::class, 'publicly reachable');
});

it('refuses a private reply carrying anything but text', function (): void {
    $fake = Meta::fake();

    expect(fn () => Meta::facebook()->message()->toComment('COMMENT_1')->image('https://example.com/a.png')->send())
        ->toThrow(MessageValidationException::class, 'Private replies carry text only');

    $fake->assertNothingSent();
});

it('refuses a template Instagram does not support and lists the ones it does', function (): void {
    $fake = Meta::fake();

    $receipt = ReceiptTemplate::make('Ada', 'ORDER-1', 'USD', 'Visa 1234', ReceiptSummary::make(9.99));

    try {
        Meta::instagram()->to('IGSID')->template($receipt)->send();
        $this->fail('Expected the receipt template to be refused.');
    } catch (UnsupportedFeatureException $e) {
        expect($e->hint())
            ->toContain('Instagram does not support receipt templates')
            ->toContain('generic, button, product');
    }

    $fake->assertNothingSent();
});

it('refuses message tags on Instagram, which has no such concept', function (): void {
    Meta::fake();

    expect(fn () => Meta::instagram()->to('IGSID')->text('Hi')->tag(MessageTag::HumanAgent)->send())
        ->toThrow(UnsupportedFeatureException::class, 'Instagram does not support message tags');
});

it('refuses any Instagram reaction other than the heart', function (): void {
    $fake = Meta::fake();

    expect(fn () => Meta::instagram()->to('IGSID')->react('mid.abc', '👍'))
        ->toThrow(MessageValidationException::class, 'only accepts the ❤ reaction');

    Meta::instagram()->to('IGSID')->react('mid.abc', 'love');

    $fake->assertSentCount(1);
});

it('captures validation failures into the response when using sendSafely', function (): void {
    $fake = Meta::fake();

    $response = Meta::instagram()->to('IGSID')->text(str_repeat('a', 1001))->sendSafely();

    expect($response->failed())->toBeTrue()
        ->and($response->error?->key)->toBe('text_too_long')
        ->and($response->error?->code)->toBeNull();

    $fake->assertNothingSent();
});

it('can be switched off entirely', function (): void {
    config()->set('meta-messaging.validate', false);

    $fake = Meta::fake();

    Meta::instagram()->to('IGSID')->text(str_repeat('a', 1001))->send();

    $fake->assertSentCount(1);
});
