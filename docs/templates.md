# Templates and buttons

Templates are immutable value objects. Every builder method returns a new instance, so a
half-built template is safe to reuse as a base.

```php
$base = GenericTemplate::make()->card(Card::make('Always shown'));

$a = $base->card(Card::make('Only in A'));
$b = $base->card(Card::make('Only in B'));
// $base still has one card
```

## Availability

| Template | Messenger | Instagram |
| --- | --- | --- |
| `GenericTemplate` | ✅ | ✅ |
| `ButtonTemplate` | ✅ | ✅ |
| `ProductTemplate` | ✅ | ✅ |
| `MediaTemplate` | ✅ | ❌ |
| `ReceiptTemplate` | ✅ | ❌ |
| `CustomerFeedbackTemplate` | ✅ | ❌ |

Sending an unavailable one raises `UnsupportedFeatureException` naming the ones that do work.

## Generic template

A carousel of up to 10 cards, 3 buttons each.

```php
use AsmaaGamal\MetaMessaging\Messages\Templates\{GenericTemplate, Card};
use AsmaaGamal\MetaMessaging\Messages\Buttons\{UrlButton, PostbackButton};

GenericTemplate::make()
    ->card(
        Card::make('Classic T-Shirt')
            ->subtitle('Soft cotton, $19')
            ->image('https://example.com/shirt.png')
            ->defaultAction(UrlButton::make('', 'https://example.com/shirt'))
            ->button(UrlButton::make('View', 'https://example.com/shirt'))
            ->button(PostbackButton::make('Buy now', 'BUY_SHIRT'))
    )
    ->square()      // crop images square instead of 1.91:1
    ->sharable();   // let recipients forward it
```

`defaultAction` is what happens when the card itself is tapped.

## Button template

Text plus up to 3 buttons.

```php
ButtonTemplate::make('What would you like to do?')
    ->button(PostbackButton::make('Track order', 'TRACK'))
    ->button(UrlButton::make('Browse', 'https://example.com'))
    ->button(CallButton::make('Call us', '+15551234567'));
```

## Media template

Full-bleed image or video with buttons. Messenger only. The media must be a reusable attachment
ID or a URL of something already hosted on Facebook.

```php
MediaTemplate::video($attachmentId)
    ->button(UrlButton::make('Watch more', 'https://example.com/videos'));

MediaTemplate::fromFacebookUrl('image', 'https://www.facebook.com/photo?fbid=...');
```

## Receipt template

An order confirmation. Messenger only.

```php
use AsmaaGamal\MetaMessaging\Messages\Templates\{
    ReceiptTemplate, ReceiptElement, ReceiptSummary, ReceiptAddress,
};

ReceiptTemplate::make(
    recipientName: 'Ada Lovelace',
    orderNumber:   'ORDER-1234',
    currency:      'USD',
    paymentMethod: 'Visa 1234',
    summary: ReceiptSummary::make(56.14)
        ->subtotal(50.00)
        ->shipping(4.95)
        ->tax(1.19),
)
    ->element(
        ReceiptElement::make('Classic T-Shirt', 25.00)
            ->quantity(2)
            ->currency('USD')
            ->image('https://example.com/shirt.png')
    )
    ->address(new ReceiptAddress('1 Main St', 'Springfield', '12345', 'IL', 'US'))
    ->adjustment('Loyalty discount', -5.00)
    ->orderUrl('https://example.com/orders/1234')
    ->merchantName('Example Store')
    ->timestamp(time());
```

Only `total_cost` is required in the summary.

## Product template

Cards drawn from a product catalog — you send IDs, Meta renders the rest.

```php
ProductTemplate::make(['SKU_1', 'SKU_2'])->product('SKU_3');
```

Up to 10 products. Requires a catalog connected to your app.

## Customer feedback template

A rating prompt. Messenger only. Answers arrive on the `messaging_feedback` webhook, not as a
message.

```php
CustomerFeedbackTemplate::make(
    title:       'How did we do?',
    subtitle:    'Your feedback helps us improve',
    buttonTitle: 'Rate us',
    privacyUrl:  'https://example.com/privacy',
)
    ->csat('q1', 'How satisfied are you?', 'Tell us more')
    ->nps('q2', 'Would you recommend us?')
    ->ces('q3', 'How easy was that?')
    ->expiresInDays(3);
```

`csat` is 1–5, `nps` 0–10, `ces` 1–7. The privacy URL is required by Meta.

## Buttons

| Button | Renders as | Notes |
| --- | --- | --- |
| `UrlButton::make($title, $url)` | `web_url` | `->webview('tall')`, `->withoutShareButton()` |
| `PostbackButton::make($title, $payload)` | `postback` | payload returns on your webhook |
| `CallButton::make($title, $e164)` | `phone_number` | number must be E.164 |
| `LoginButton::make($url)` | `account_link` | starts account linking |
| `LogoutButton::make()` | `account_unlink` | no title or URL |
| `GamePlayButton::make($title, $payload, $meta)` | `game_play` | Instant Games |

Titles are capped at 20 characters and checked before sending. URLs are validated too — Meta
fetches them over the public internet, so they must be absolute and reachable.

```php
UrlButton::make('Open', 'https://example.com')
    ->webview('full', messengerExtensions: true, fallbackUrl: 'https://example.com/plain')
    ->withoutShareButton();
```

Webview height accepts `compact`, `tall`, or `full`.

## Limits, enforced locally

| Limit | Value |
| --- | --- |
| Cards per generic template | 10 |
| Buttons per card | 3 |
| Buttons per button template | 3 |
| Products per product template | 10 |
| Quick replies per message | 13 |
| Button and quick reply title | 20 characters |
| Card title and subtitle | 80 characters |
| Button template text | 640 characters |

Each is checked by the object that owns it — cards check their buttons, templates check their
cards — so the first thing wrong is the thing reported.
