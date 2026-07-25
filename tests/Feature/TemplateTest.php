<?php

declare(strict_types=1);

use AsmaaGamal\MetaMessaging\Facades\Meta;
use AsmaaGamal\MetaMessaging\Messages\Buttons\CallButton;
use AsmaaGamal\MetaMessaging\Messages\Buttons\GamePlayButton;
use AsmaaGamal\MetaMessaging\Messages\Buttons\LoginButton;
use AsmaaGamal\MetaMessaging\Messages\Buttons\LogoutButton;
use AsmaaGamal\MetaMessaging\Messages\Buttons\PostbackButton;
use AsmaaGamal\MetaMessaging\Messages\Buttons\UrlButton;
use AsmaaGamal\MetaMessaging\Messages\Templates\ButtonTemplate;
use AsmaaGamal\MetaMessaging\Messages\Templates\Card;
use AsmaaGamal\MetaMessaging\Messages\Templates\CustomerFeedbackTemplate;
use AsmaaGamal\MetaMessaging\Messages\Templates\GenericTemplate;
use AsmaaGamal\MetaMessaging\Messages\Templates\MediaTemplate;
use AsmaaGamal\MetaMessaging\Messages\Templates\ProductTemplate;
use AsmaaGamal\MetaMessaging\Messages\Templates\ReceiptAddress;
use AsmaaGamal\MetaMessaging\Messages\Templates\ReceiptElement;
use AsmaaGamal\MetaMessaging\Messages\Templates\ReceiptSummary;
use AsmaaGamal\MetaMessaging\Messages\Templates\ReceiptTemplate;
use AsmaaGamal\MetaMessaging\Transport\FakeTransport;

function sentTemplate(): array
{
    /** @var FakeTransport $fake */
    $fake = Meta::faked();

    return $fake->lastRequest()?->payload['message']['attachment'] ?? [];
}

it('renders a generic template exactly as Meta documents it', function (): void {
    Meta::fake();

    Meta::facebook()->to('PSID')->template(
        GenericTemplate::make()
            ->card(
                Card::make('Classic T-Shirt')
                    ->subtitle('Soft cotton')
                    ->image('https://example.com/shirt.png')
                    ->defaultAction(UrlButton::make('', 'https://example.com/shirt'))
                    ->button(UrlButton::make('View', 'https://example.com/shirt'))
                    ->button(PostbackButton::make('Buy', 'BUY_SHIRT'))
            )
            ->square()
    )->send();

    expect(sentTemplate())->toBe([
        'type' => 'template',
        'payload' => [
            'template_type' => 'generic',
            'elements' => [[
                'title' => 'Classic T-Shirt',
                'subtitle' => 'Soft cotton',
                'image_url' => 'https://example.com/shirt.png',
                'default_action' => [
                    'type' => 'web_url',
                    'url' => 'https://example.com/shirt',
                ],
                'buttons' => [
                    ['type' => 'web_url', 'title' => 'View', 'url' => 'https://example.com/shirt'],
                    ['type' => 'postback', 'title' => 'Buy', 'payload' => 'BUY_SHIRT'],
                ],
            ]],
            'image_aspect_ratio' => 'square',
        ],
    ]);
});

it('renders a button template with every button type', function (): void {
    Meta::fake();

    Meta::facebook()->to('PSID')->template(
        ButtonTemplate::make('What next?')
            ->button(UrlButton::make('Open', 'https://example.com')->webview('tall'))
            ->button(CallButton::make('Call us', '+15551234567'))
            ->button(LogoutButton::make())
    )->send();

    expect(sentTemplate()['payload'])->toBe([
        'template_type' => 'button',
        'text' => 'What next?',
        'buttons' => [
            [
                'type' => 'web_url',
                'title' => 'Open',
                'url' => 'https://example.com',
                'webview_height_ratio' => 'tall',
            ],
            ['type' => 'phone_number', 'title' => 'Call us', 'payload' => '+15551234567'],
            ['type' => 'account_unlink'],
        ],
    ]);
});

it('renders login and game play buttons', function (): void {
    Meta::fake();

    Meta::facebook()->to('PSID')->template(
        ButtonTemplate::make('Sign in')
            ->button(LoginButton::make('https://example.com/auth'))
            ->button(GamePlayButton::make('Play', 'PLAY', ['player_id' => '7']))
    )->send();

    expect(sentTemplate()['payload']['buttons'])->toBe([
        ['type' => 'account_link', 'url' => 'https://example.com/auth'],
        ['type' => 'game_play', 'title' => 'Play', 'payload' => 'PLAY', 'game_metadata' => ['player_id' => '7']],
    ]);
});

it('renders a media template from a reusable attachment', function (): void {
    Meta::fake();

    Meta::facebook()->to('PSID')->template(
        MediaTemplate::video('987654321')->button(UrlButton::make('Watch', 'https://example.com/v'))
    )->send();

    expect(sentTemplate()['payload'])->toBe([
        'template_type' => 'media',
        'elements' => [[
            'media_type' => 'video',
            'attachment_id' => '987654321',
            'buttons' => [
                ['type' => 'web_url', 'title' => 'Watch', 'url' => 'https://example.com/v'],
            ],
        ]],
    ]);
});

it('renders a receipt template with items, address, summary and adjustments', function (): void {
    Meta::fake();

    Meta::facebook()->to('PSID')->template(
        ReceiptTemplate::make(
            'Ada Lovelace',
            'ORDER-1234',
            'USD',
            'Visa 1234',
            ReceiptSummary::make(56.14)->subtotal(50.00)->shipping(4.95)->tax(1.19),
        )
            ->element(ReceiptElement::make('Classic T-Shirt', 25.00)->quantity(2)->currency('USD'))
            ->address(new ReceiptAddress('1 Main St', 'Springfield', '12345', 'IL', 'US'))
            ->adjustment('Loyalty discount', -5.00)
            ->orderUrl('https://example.com/orders/1234')
    )->send();

    $payload = sentTemplate()['payload'];

    expect($payload['template_type'])->toBe('receipt')
        ->and($payload['recipient_name'])->toBe('Ada Lovelace')
        ->and($payload['order_number'])->toBe('ORDER-1234')
        ->and($payload['summary'])->toBe([
            'subtotal' => 50.00,
            'shipping_cost' => 4.95,
            'total_tax' => 1.19,
            'total_cost' => 56.14,
        ])
        ->and($payload['elements'][0])->toBe([
            'title' => 'Classic T-Shirt',
            'quantity' => 2,
            'price' => 25.00,
            'currency' => 'USD',
        ])
        ->and($payload['address']['city'])->toBe('Springfield')
        ->and($payload['adjustments'])->toBe([['name' => 'Loyalty discount', 'amount' => -5.00]]);
});

it('renders a product template', function (): void {
    Meta::fake();

    Meta::facebook()->to('PSID')->template(
        ProductTemplate::make(['SKU_1'])->product('SKU_2')
    )->send();

    expect(sentTemplate()['payload'])->toBe([
        'template_type' => 'product',
        'elements' => [['id' => 'SKU_1'], ['id' => 'SKU_2']],
    ]);
});

it('renders a customer feedback template', function (): void {
    Meta::fake();

    Meta::facebook()->to('PSID')->template(
        CustomerFeedbackTemplate::make('How did we do?', 'Rate your experience', 'Rate us', 'https://example.com/privacy')
            ->csat('q1', 'How satisfied are you?', 'Tell us more')
            ->expiresInDays(3)
    )->send();

    expect(sentTemplate()['payload'])->toBe([
        'template_type' => 'customer_feedback',
        'title' => 'How did we do?',
        'subtitle' => 'Rate your experience',
        'button_title' => 'Rate us',
        'feedback_screens' => [[
            'questions' => [[
                'id' => 'q1',
                'type' => 'csat',
                'title' => 'How satisfied are you?',
                'follow_up' => ['type' => 'free_form', 'placeholder' => 'Tell us more'],
            ]],
        ]],
        'business_privacy' => ['url' => 'https://example.com/privacy'],
        'expires_in_days' => 3,
    ]);
});

it('keeps templates immutable so a base can be reused', function (): void {
    $base = GenericTemplate::make()->card(Card::make('One'));
    $extended = $base->card(Card::make('Two'));

    expect($base->cards)->toHaveCount(1)
        ->and($extended->cards)->toHaveCount(2);
});
