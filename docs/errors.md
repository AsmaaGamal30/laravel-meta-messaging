# Error reference

Every failure — local or remote — arrives as a `MetaError`, and every `MetaError` can be raised
as a typed exception or returned inside a failed `MessageResponse`. Both carry identical
information.

## The shape of an error

```php
$error->key;           // 'window_expired' — stable slug, safe to switch on
$error->message;       // Meta's own text, or ours for local failures
$error->hint;          // plain-English explanation of the fix
$error->channel;       // Channel::Facebook
$error->code;          // 10
$error->subcode;       // 2018278
$error->type;          // 'OAuthException'
$error->userTitle;     // Meta's error_user_title, when present
$error->userMessage;   // Meta's error_user_msg, when present
$error->traceId;       // fbtrace_id — quote this to Meta support
$error->status;        // HTTP status
$error->endpoint;      // '1010101/messages'
$error->isRetryable(); // whether the identical request could succeed later
$error->context();     // the request that failed, credentials removed
$error->toArray();     // all of the above, ready to log
```

Switch on `key` rather than `code`. Keys are part of this package's API and will not change
within a major version; Meta's codes move around.

## Exceptions

All extend `MetaMessagingException`, which extends `RuntimeException`.

| Exception | Raised when |
| --- | --- |
| `MessagingWindowExpiredException` | outside the 24-hour window, or a daily limit is hit |
| `RecipientUnavailableException` | blocked, chat deleted, or privacy refuses businesses |
| `RecipientNotFoundException` | no user matches the ID — usually the wrong account |
| `PrivateReplyNotAllowedException` | this person's settings refuse private replies |
| `PrivateReplyAlreadySentException` | one private reply per comment; already used |
| `InvalidCommentException` | comment invalid, deleted, or not on your content |
| `InvalidAccessTokenException` | token expired, revoked, malformed, or not an admin's |
| `PermissionDeniedException` | a required scope is missing, or the app is unapproved |
| `RateLimitExceededException` | app, page, or endpoint throttling — retryable |
| `TemporarilyBlockedException` | Meta blocked the app or Page, usually for policy |
| `AttachmentException` | media unreachable, unsupported, or oversized |
| `TransportException` | request never completed, or the response was unreadable |
| `DeprecatedMessageTagException` | a retired message tag — caught locally |
| `UnsupportedFeatureException` | the channel lacks this feature — caught locally |
| `MessageValidationException` | a documented limit is broken — caught locally |
| `InvalidConfigurationException` | credentials or version are wrong — caught locally |
| `MetaApiException` | any Meta error with no more specific mapping |

## Retired message tags

Meta retired `CONFIRMED_EVENT_UPDATE`, `ACCOUNT_UPDATE`, and `POST_PURCHASE_UPDATE` on
**27 April 2026**. Requests carrying one now fail with a bare `(#100) Invalid parameter` that
never mentions the tag — which makes this one of the hardest Meta failures to diagnose from the
response alone.

This package refuses them before sending:

```php
Meta::facebook()->to($psid)->text('Shipped')->tag(MessageTag::AccountUpdate)->send();
// DeprecatedMessageTagException:
// "Meta retired the ACCOUNT_UPDATE message tag on 2026-04-27. Requests using it now fail with
//  a bare "(#100) Invalid parameter", which does not say the tag is at fault. Instead, use a
//  Utility Template via the Marketing Messages API, or the HUMAN_AGENT tag if a person is
//  replying."
```

Still supported: `HUMAN_AGENT` and `CUSTOMER_FEEDBACK`, both valid for 7 days after the person's
last message. `HUMAN_AGENT` requires the Human Agent feature to be approved for your app.

## The messaging window

Meta allows free-form messages only within 24 hours of the person's last message to you. This
package does **not** track that window — it maps Meta's answer instead:

```php
catch (MessagingWindowExpiredException $e) {
    // "The 24 hour messaging window has closed. Meta only allows a free-form message within
    //  24 hours of the person's last message to you. To reply later, use the HUMAN_AGENT tag
    //  (valid for 7 days, requires the Human Agent feature to be approved) or reach them
    //  through the Marketing Messages API."
}
```

If you want to fail fast instead of spending an API call, record inbound message timestamps from
your webhook and check them before calling `send()`.

## People who cannot receive private replies

Three distinct failures, three distinct exceptions:

```php
try {
    Meta::facebook()->privateReply($commentId, 'Details in your DMs');
} catch (PrivateReplyNotAllowedException $e) {
    // Their privacy settings refuse it, or the Page has messaging off.
    // Fall back to a public reply:
    Meta::facebook()->replyToComment($commentId, 'Sent you the details — check your inbox!');
} catch (PrivateReplyAlreadySentException $e) {
    // One per comment. The thread continues only if they answer.
} catch (InvalidCommentException $e) {
    // Deleted, or not on content this account owns.
}
```

Private replies are **text only** — Meta accepts a request carrying media and then silently
delivers only the text. This package refuses that up front instead.

The window is 7 days from when the comment was created; for Instagram Live, only during the
broadcast.

## Rate limits

`RateLimitExceededException` is retryable. Check `X-App-Usage` in Meta's response headers to see
how close you are. Documented ceilings on Instagram: 100 text sends/second, 10 media
sends/second, 750 private replies/hour.

## Adding a code

The catalog is a plain table, so a newly observed code needs no package release:

```php
// In a service provider
use AsmaaGamal\MetaMessaging\Exceptions\{ErrorCatalog, RateLimitExceededException};

ErrorCatalog::extend(
    code: 4242,
    exception: RateLimitExceededException::class,
    key: 'rate_limited',
    retryable: true,
);
```

Rules can match on `code`, `subcode`, or a regex against Meta's message. A subcode is the most
specific signal, then a message pattern, then a bare code. Runtime rules are checked before the
built-ins, so you can override a mapping you disagree with.

Anything unmatched becomes a `MetaApiException` carrying Meta's own description — nothing is
swallowed.

## Rewording the hints

Hints live in language files:

```bash
php artisan vendor:publish --tag=meta-messaging-lang
```

Then edit `lang/vendor/meta-messaging/en/errors.php`, or add another locale beside it.
