# Laravel Meta Messaging

[![Tests](https://github.com/AsmaaGamal30/laravel-meta-messaging/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/AsmaaGamal30/laravel-meta-messaging/actions/workflows/tests.yml)
[![Code quality](https://github.com/AsmaaGamal30/laravel-meta-messaging/actions/workflows/quality.yml/badge.svg?branch=main)](https://github.com/AsmaaGamal30/laravel-meta-messaging/actions/workflows/quality.yml)
[![PHP](https://img.shields.io/badge/php-8.2%20%7C%208.3%20%7C%208.4-777BB4)](composer.json)
[![Laravel](https://img.shields.io/badge/laravel-12%20%7C%2013-FF2D20)](composer.json)
[![License](https://img.shields.io/github/license/AsmaaGamal30/laravel-meta-messaging)](LICENSE.md)

<!--
  Once the package is live on Packagist, add these back:
  [![Latest Version](https://img.shields.io/packagist/v/asmaa-gamal/laravel-meta-messaging.svg)](https://packagist.org/packages/asmaa-gamal/laravel-meta-messaging)
  [![Downloads](https://img.shields.io/packagist/dt/asmaa-gamal/laravel-meta-messaging.svg)](https://packagist.org/packages/asmaa-gamal/laravel-meta-messaging)
-->


Send Facebook Messenger and Instagram messages from Laravel — every message type, with a fluent
API, pre-flight validation, and errors that actually tell you what went wrong.

```php
Meta::facebook()->to($psid)->text('Your order shipped!')->send();
```

## Why this exists

Calling Meta's Send API is easy. Understanding why it failed is not.

```
(#551) This person isn't available right now
(#100) Invalid parameter
```

Neither says what to do. The first means the recipient blocked you, deleted the chat, or
restricts third-party apps — permanently, for that person. The second could be almost anything;
most often it is a message tag [Meta retired on 27 April 2026](docs/errors.md#retired-message-tags)
without changing the error response, so code that worked for years now fails with an error that
never mentions tags.

This package turns those into:

```
Facebook Messenger #551 This person isn't available right now. — This person cannot receive
your message. They have blocked the Facebook Messenger account, deleted the conversation, or
set their privacy to refuse messages from businesses. Nothing in the request can be changed to
make this succeed — treat it as permanent for this recipient.
```

and refuses the retired tag *before* spending an API call, naming the tag, the date, and the
replacement.

## Installation

```bash
composer require asmaa-gamal/laravel-meta-messaging
```

Publish the config if you want to edit it:

```bash
php artisan vendor:publish --tag=meta-messaging-config
```

Add your credentials to `.env`:

```dotenv
META_GRAPH_VERSION=v25.0

META_FACEBOOK_PAGE_ID=your-page-id
META_FACEBOOK_PAGE_TOKEN=your-page-access-token

META_INSTAGRAM_ACCOUNT_ID=your-instagram-account-id
META_INSTAGRAM_TOKEN=your-instagram-token
META_INSTAGRAM_LOGIN_TYPE=instagram

# Optional, but recommended — enables appsecret_proof on every request
META_APP_SECRET=your-app-secret
```

Requires PHP 8.2+ and Laravel 12 or 13.

> Laravel 11 is not supported. It has reached end of life with open security advisories, so
> Composer refuses to install any 11.x release — there is no version of it this package could
> target safely.

## Quick start

```php
use AsmaaGamal\MetaMessaging\Facades\Meta;

// Text
Meta::facebook()->to($psid)->text('Hello!')->send();

// Media — a URL, a local file, or a reusable attachment ID; the source is inferred
Meta::facebook()->to($psid)->image('https://example.com/photo.jpg')->send();
Meta::facebook()->to($psid)->video('/local/path/clip.mp4')->send();
Meta::facebook()->to($psid)->audio('1234567890')->send();
Meta::facebook()->to($psid)->file('https://example.com/invoice.pdf')->send();

// Reply to a specific message, and react to one
Meta::facebook()->to($psid)->text('On it')->replyTo($mid)->send();
Meta::facebook()->to($psid)->react($mid, '❤');
Meta::facebook()->to($psid)->unreact($mid);

// Comments
Meta::facebook()->privateReply($commentId, 'Sent you the details in DM');
Meta::facebook()->replyToComment($commentId, 'Thanks for the kind words!');

// Instagram — same API, narrower feature set
Meta::instagram()->to($igsid)->text('Thanks for reaching out!')->send();
```

## Templates

Every Messenger template type, composed from immutable value objects:

```php
use AsmaaGamal\MetaMessaging\Messages\Templates\{GenericTemplate, Card};
use AsmaaGamal\MetaMessaging\Messages\Buttons\{UrlButton, PostbackButton};

Meta::facebook()->to($psid)->template(
    GenericTemplate::make()
        ->card(
            Card::make('Classic T-Shirt')
                ->subtitle('Soft cotton, $19')
                ->image('https://example.com/shirt.png')
                ->button(UrlButton::make('View', 'https://example.com/shirt'))
                ->button(PostbackButton::make('Buy now', 'BUY_SHIRT'))
        )
)->send();
```

Also available: `ButtonTemplate`, `MediaTemplate`, `ReceiptTemplate`, `ProductTemplate`,
`CustomerFeedbackTemplate`. Buttons: `UrlButton`, `PostbackButton`, `CallButton`, `LoginButton`,
`LogoutButton`, `GamePlayButton`. See [docs/templates.md](docs/templates.md).

## Quick replies

```php
use AsmaaGamal\MetaMessaging\Messages\QuickReply;

Meta::facebook()->to($psid)
    ->text('How can we help?')
    ->quickReplies([
        QuickReply::text('Track my order', 'TRACK'),
        QuickReply::text('Talk to a human', 'AGENT'),
        QuickReply::email(),
    ])
    ->send();
```

## Choosing the Graph API version

Globally in config, per account, or per call:

```php
config(['meta-messaging.version' => 'v25.0']);           // default for everything

Meta::facebook()->usingVersion('v23.0')->to($psid)->text('Hi')->send();
```

Per account, in `config/meta-messaging.php`:

```php
'accounts' => [
    'facebook' => [
        'default' => ['page_id' => '...', 'token' => '...'],
        'legacy'  => ['page_id' => '...', 'token' => '...', 'version' => 'v21.0'],
    ],
],
```

Then `Meta::facebook('legacy')`. A malformed version is rejected immediately with an explanation
rather than becoming a confusing 404.

## Multiple accounts and runtime tokens

```php
Meta::facebook('second_page')->to($psid)->text('Hi')->send();

// Multi-tenant: resolve the token at runtime
Meta::facebook()->usingToken($tenant->page_token, $tenant->page_id)
    ->to($psid)->text('Hi')->send();
```

## Error handling

Two styles, same information.

**Typed exceptions** (default):

```php
use AsmaaGamal\MetaMessaging\Exceptions\{
    MessagingWindowExpiredException,
    RecipientUnavailableException,
    PrivateReplyNotAllowedException,
    MetaMessagingException,
};

try {
    Meta::facebook()->to($psid)->text('Hello')->send();
} catch (MessagingWindowExpiredException $e) {
    // Outside 24 hours — queue it for the next time they message you
} catch (RecipientUnavailableException $e) {
    // Permanent for this person — stop retrying, mark them unreachable
} catch (MetaMessagingException $e) {
    report($e);
}
```

**Structured results** (never throws):

```php
$response = Meta::facebook()->to($psid)->text('Hello')->sendSafely();

if ($response->failed()) {
    $error = $response->error();

    $error->key;          // 'window_expired' — stable, safe to switch on
    $error->hint;         // plain-English explanation of the fix
    $error->code;         // 10
    $error->subcode;      // 2018278
    $error->traceId;      // Meta's fbtrace_id, for support tickets
    $error->isRetryable(); // false
    $error->toArray();    // everything, ready to log
}
```

Access tokens are stripped from error context before it reaches your logs.

Every exception carries `key()`, `hint()`, `apiCode()`, `subcode()`, `traceId()`, `channel()`,
`endpoint()`, `isRetryable()`, `context()`, and `toArray()`.

See [docs/errors.md](docs/errors.md) for the full error reference.

### Caught before the network

These never cost you an API call or a rate-limit slot:

| Refused locally | Instead of Meta's |
| --- | --- |
| A retired message tag | `(#100) Invalid parameter` |
| A receipt template sent to Instagram | `(#100) Invalid parameter` |
| Text past the channel limit (1,000 IG / 2,000 FB) | `(#100) Invalid parameter` |
| More than 10 cards, 3 buttons, or 13 quick replies | `(#100) Invalid parameter` |
| A private reply carrying media | silent delivery of text only |
| Any Instagram reaction but ❤ | `(#100) Invalid parameter` |
| A missing or malformed credential | `An access token is required` |

Turn it off with `META_VALIDATE=false` if you ever need to.

## Queueing

```php
Meta::facebook()->to($psid)->text('Hello')->queue();
```

Validation still runs at the call site, so a malformed message fails immediately rather than on a
worker. The job retries throttling and network faults with backoff, and fails permanent errors
outright instead of burning attempts.

## Events

```php
use AsmaaGamal\MetaMessaging\Events\{MetaMessageSent, MetaMessageFailed};
```

`MetaMessageFailed` fires before any exception is thrown, so you can record delivery failures
without wrapping every send in a try/catch.

## Testing

```php
use AsmaaGamal\MetaMessaging\Facades\Meta;
use AsmaaGamal\MetaMessaging\Transport\MetaRequest;

it('greets the customer', function () {
    Meta::fake();

    (new WelcomeFlow)->run($psid);

    Meta::assertSent(fn (MetaRequest $r) => $r->payload['message']['text'] === 'Welcome!');
});
```

Simulate failures with the real error mapping:

```php
Meta::fake()->respondWithError(551, "This person isn't available right now.");

expect(fn () => Meta::facebook()->to($psid)->text('Hi')->send())
    ->toThrow(RecipientUnavailableException::class);
```

## Channel differences

|  | Messenger | Instagram |
| --- | --- | --- |
| Host | `graph.facebook.com` | `graph.instagram.com` or `graph.facebook.com` |
| Text limit | 2,000 | 1,000 |
| Templates | generic, button, media, receipt, product, customer feedback | generic, button, product |
| Reactions | any emoji | ❤ only |
| Comment reply | `/{comment-id}/comments` | `/{comment-id}/replies` |
| Reusable uploads | yes | no |
| Message tags, personas | yes | no |

Anything a channel lacks is refused locally with a message naming the alternatives.

## Documentation

- [Configuration](docs/configuration.md)
- [Sending messages](docs/sending.md)
- [Templates and buttons](docs/templates.md)
- [Comments and private replies](docs/comments.md)
- [Error reference](docs/errors.md)
- [Testing](docs/testing.md)

## Contributing

Bug reports and pull requests are welcome — see [CONTRIBUTING.md](CONTRIBUTING.md). New Meta
error codes are especially welcome; adding one is a single line in `ErrorCatalog`.

## Credits

Built by [Asmaa Gamal](https://github.com/AsmaaGamal30).

## License

MIT. See [LICENSE.md](LICENSE.md).
