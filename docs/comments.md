# Comments and private replies

Two ways to answer someone who commented on your content: privately, in their inbox, or publicly
in the thread.

## Private replies

Sends a direct message to whoever wrote a comment, even though they have never messaged you.

```php
Meta::facebook()->privateReply($commentId, 'Sent you the details in your inbox!');
Meta::instagram()->privateReply($commentId, 'Thanks! Replying privately.');
```

Under the hood this posts to the messages endpoint with a `comment_id` recipient, which is why it
lives on the channel rather than the builder.

### Rules Meta enforces

| Rule | Consequence |
| --- | --- |
| One per comment | `PrivateReplyAlreadySentException` on the second |
| Within 7 days of the comment | `MessagingWindowExpiredException` after |
| Instagram Live: during the broadcast only | fails once the stream ends |
| Text only | media is silently dropped — refused locally instead |
| Only on content you own | `InvalidCommentException` |
| The person's privacy must permit it | `PrivateReplyNotAllowedException` |

Once they reply, a normal 24-hour conversation opens and you can send anything.

### People who do not allow private replies

Some people turn off private replies entirely. Nothing about the request can change that, so
handle it and fall back:

```php
use AsmaaGamal\MetaMessaging\Exceptions\{
    PrivateReplyNotAllowedException,
    PrivateReplyAlreadySentException,
    InvalidCommentException,
};

try {
    Meta::facebook()->privateReply($commentId, 'Details are in your inbox');
} catch (PrivateReplyNotAllowedException) {
    Meta::facebook()->replyToComment($commentId, 'Details are on our site — link in bio!');
} catch (PrivateReplyAlreadySentException) {
    // Already handled; nothing to do.
} catch (InvalidCommentException) {
    // Deleted or not ours — drop it.
}
```

Or branch on the result instead of catching:

```php
$response = Meta::facebook()->withoutExceptions()->privateReply($commentId, 'Hi');

if ($response->failed() && $response->error()->key === 'private_reply_not_allowed') {
    Meta::facebook()->replyToComment($commentId, 'Check our site for details!');
}
```

### Media in a private reply

Meta accepts the request and delivers only the text — a silent, confusing partial success. This
package refuses it:

```php
Meta::facebook()->message()->toComment($commentId)->image($url)->send();
// MessageValidationException: "Private replies carry text only. Meta silently drops
// attachments, templates, and quick replies from them. Send the media as a follow-up once the
// person answers."
```

Send the text first; once they reply, the window is open for media.

## Public comment replies

```php
Meta::facebook()->replyToComment($commentId, 'Thanks for the kind words!');
Meta::instagram()->replyToComment($commentId, 'Appreciate it!');
```

The endpoints differ — Messenger nests replies under `/comments`, Instagram under `/replies` —
and the package picks the right one.

## New top-level comments

```php
Meta::facebook()->comment($postId, 'Back in stock!');
Meta::instagram()->comment($mediaId, 'New drop is live.');
```

## Replying to a post author

```php
Meta::facebook()->message()->toPost($postId)->text('Thanks for posting!')->send();
```

## A complete comment handler

```php
public function handle(string $commentId, string $text): void
{
    if (! str_contains(strtolower($text), 'price')) {
        return;
    }

    $response = Meta::facebook()
        ->withoutExceptions()
        ->privateReply($commentId, 'Our current pricing is at example.com/pricing.');

    if ($response->successful) {
        return;
    }

    match ($response->error()->key) {
        // They refuse private replies, or already got one — answer in public.
        'private_reply_not_allowed',
        'private_reply_already_sent' => Meta::facebook()
            ->replyToComment($commentId, 'Pricing is at example.com/pricing 🙂'),

        // Gone, or never ours. Nothing to do.
        'invalid_comment' => null,

        default => report(new RuntimeException($response->error()->summary())),
    };
}
```

## Permissions

**Messenger** — `pages_messaging` for private replies, `pages_manage_engagement` for public
replies, `pages_read_engagement` to read comments. Private replies also need the Human Agent
feature.

**Instagram** — `instagram_manage_comments`, plus the messaging scope for your login flow.

All require Advanced Access to work with people who have no role on your app.

## Rate limits

Instagram allows 750 private replies per hour. Exceeding it raises
`RateLimitExceededException`, which is retryable — back off and try again.
