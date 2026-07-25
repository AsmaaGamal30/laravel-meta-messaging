# Testing

`Meta::fake()` swaps the transport for one that records requests instead of sending them.
Everything above the network — the builder, the validators, the error mapping — runs exactly as
it does in production.

```php
use AsmaaGamal\MetaMessaging\Facades\Meta;
use AsmaaGamal\MetaMessaging\Transport\MetaRequest;

it('welcomes a new customer', function () {
    Meta::fake();

    (new WelcomeFlow)->run('PSID123');

    Meta::assertSent(fn (MetaRequest $r) => $r->payload['message']['text'] === 'Welcome!');
});
```

## Assertions

```php
Meta::assertSent();                       // anything at all
Meta::assertSent(fn ($r) => /* ... */);   // something matching
Meta::assertNotSent(fn ($r) => /* ... */);
Meta::assertNothingSent();
```

Or hold the fake for more:

```php
$fake = Meta::fake();

// ...

$fake->assertSentCount(2);
$fake->lastRequest();   // the most recent MetaRequest
$fake->requests();      // all of them, in order
```

## Inspecting a request

```php
$request = $fake->lastRequest();

$request->url();          // https://graph.facebook.com/v25.0/1010101/messages
$request->path;           // 1010101/messages
$request->payload;        // the body, without credentials
$request->body();         // the body Meta receives, including access_token
$request->safePayload();  // credentials redacted, for logging
$request->channel();      // Channel::Facebook
```

## Canned responses

```php
Meta::fake()->respondWith([
    'recipient_id' => 'PSID123',
    'message_id'   => 'mid.custom',
]);
```

Without a stub, the fake answers successfully with a generated `message_id`.

## Simulating failures

`respondWithError()` runs through the real catalog, so you get the same exception and hint
production would produce:

```php
use AsmaaGamal\MetaMessaging\Exceptions\{
    RecipientUnavailableException,
    MessagingWindowExpiredException,
};

Meta::fake()->respondWithError(551, "This person isn't available right now.");

expect(fn () => Meta::facebook()->to('PSID')->text('Hi')->send())
    ->toThrow(RecipientUnavailableException::class);
```

With a subcode:

```php
Meta::fake()->respondWithError(
    code: 10,
    message: 'This message is sent outside of allowed window.',
    subcode: 2018278,
);
```

Conditionally, so one call succeeds and another fails:

```php
Meta::fake()->respondWithError(
    code: 551,
    message: 'Unavailable',
    when: fn (MetaRequest $r) => $r->payload['recipient']['id'] === 'BLOCKED_USER',
);
```

## Testing your fallbacks

The point of the error layer is the branch you take afterwards, so test that:

```php
it('replies publicly when a private reply is refused', function () {
    $fake = Meta::fake()->respondWithError(
        100,
        "This person's settings don't allow others to respond in private to their comment.",
    );

    (new CommentHandler)->handle('COMMENT_1', 'what is the price?');

    $fake->assertSent(fn (MetaRequest $r) => $r->path === 'COMMENT_1/comments');
});
```

## Testing against the real HTTP layer

For the exact bytes on the wire, skip the fake and use `Http::fake()` — the package uses
Laravel's HTTP client throughout:

```php
use Illuminate\Support\Facades\Http;

Http::fake(['graph.facebook.com/*' => Http::response(['message_id' => 'mid.real'])]);

$response = Meta::facebook()->to('PSID')->text('Hello')->send();

Http::assertSent(fn ($request) => $request->data() === [
    'recipient'    => ['id' => 'PSID'],
    'message'      => ['text' => 'Hello'],
    'access_token' => 'page-token',
]);
```

Useful for connection failures too:

```php
Http::fake(fn () => throw new ConnectionException('Connection timed out'));
```

## Events and queues

```php
Event::fake([MetaMessageSent::class]);
Queue::fake();

Meta::fake();

Meta::facebook()->to('PSID')->text('Hi')->queue();

Queue::assertPushed(SendMetaMessageJob::class);
```

Validation runs before queueing, so an invalid message throws at the call site and pushes
nothing.

## Building a request without sending

```php
$payload = Meta::facebook()->to('PSID')->text('Hi')->toPayload();
$request = Meta::facebook()->to('PSID')->text('Hi')->request();
```

No fake needed — useful for asserting a builder produces the shape Meta documents.
