# Contributing

Thanks for helping out.

## Getting set up

```bash
git clone https://github.com/AsmaaGamal30/laravel-meta-messaging.git
cd laravel-meta-messaging
composer install
composer test
```

No Meta credentials are needed. Nothing in the test suite touches the network.

## Before opening a pull request

```bash
composer test     # Pest
composer lint     # Pint, in check mode
composer analyse  # PHPStan level 6
```

`composer format` applies Pint's fixes.

## Adding a Meta error code

The most useful contribution, and the smallest. Meta ships codes without documenting them, so
real-world sightings are valuable.

1. Add a row to `ErrorCatalog::rules()`:

   ```php
   self::rule(2018108, RecipientUnavailableException::class, 'recipient_unavailable'),
   ```

2. Add the hint to `lang/en/errors.php` if the key is new. Explain the **cause and the fix**, not
   just a restatement of Meta's message — that is the whole point of the package.

3. Add a case to the dataset in `tests/Feature/ErrorHandlingTest.php`.

Please include the raw error body you saw, with credentials removed. Redacted real payloads are
far more useful than invented ones.

## Adding a message type or template

- Templates implement `Contracts\Template`, buttons implement `Contracts\Button`, content
  implements `Contracts\MessageContent`.
- Keep them immutable — builder methods return a new instance.
- Declare the matching `Capability` and add it to whichever channels support it. If only one
  channel does, the capability validator handles the other automatically.
- Each object validates its own limits in `validate()`; nested objects recurse.
- Assert the rendered payload against Meta's documented example in a test.

## Style

- PHP 8.2+, `declare(strict_types=1)`, everything type-hinted.
- Pint's `laravel` preset, enforced in CI.
- Comments explain **why**, not what. Skip the ones that restate the code.
- Public methods get a docblock.

## Reporting a bug

Include the Meta error body (redacted), the package version, PHP and Laravel versions, and the
code that triggered it. `$exception->toArray()` captures most of this already and is safe to
paste — access tokens are stripped from it.

## Scope

This package sends. Receiving webhooks and tracking the 24-hour messaging window are deliberately
out of scope: they need storage and routing decisions that belong to the application, not to a
library. Error mapping tells you when a window has closed.
