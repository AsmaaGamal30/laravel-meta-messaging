# Sending messages

Every message starts from a channel and ends in a terminal call.

```php
Meta::facebook()->to($psid)->text('Hello')->send();
```

## Recipients

```php
->to($id)               // page-scoped ID (Messenger) or Instagram-scoped ID
->toComment($commentId) // private reply to whoever wrote a comment
->toPost($postId)       // private reply to whoever wrote a post
->toUserRef($ref)       // user_ref from the checkbox plugin
```

Page-scoped IDs are only valid for the Page that issued them, and Instagram-scoped IDs only for
Instagram. Crossing them gives `RecipientNotFoundException`, whose hint says exactly that.

## Content

A message carries one content item.

```php
->text('Hello')
->image($source)
->audio($source)
->video($source)
->file($source)
->template($template)
```

`$source` can be any of three things, inferred from its shape:

| Value | Treated as |
| --- | --- |
| `https://example.com/a.png` | a public URL for Meta to fetch |
| `/var/www/storage/a.png` | a local file, uploaded as multipart |
| `1234567890` | a previously uploaded attachment ID |

Be explicit when you prefer:

```php
use AsmaaGamal\MetaMessaging\Messages\Content\AttachmentContent;
use AsmaaGamal\MetaMessaging\Enums\AttachmentType;

AttachmentContent::fromUrl(AttachmentType::Image, $url);
AttachmentContent::fromPath(AttachmentType::Video, $path);
AttachmentContent::fromAttachmentId(AttachmentType::Audio, $id);
```

Size ceilings are checked locally for local files: 8MB for images, 25MB for audio, video, and
files. URLs cannot be checked ahead of time, so an oversized one comes back as
`AttachmentException`.

### Reusable attachments

Upload once, send many times — and the only way to put media into a `MediaTemplate`:

```php
$id = Meta::facebook()
    ->uploadAttachment(AttachmentType::Image, 'https://example.com/logo.png')
    ->attachmentId();

Meta::facebook()->to($psid)->image($id)->send();
```

Messenger only; Instagram refuses with `UnsupportedFeatureException`.

## Quick replies

Up to 13, each title up to 20 characters.

```php
use AsmaaGamal\MetaMessaging\Messages\QuickReply;

->quickReplies([
    QuickReply::text('Yes', 'PAYLOAD_YES'),
    QuickReply::text('No', 'PAYLOAD_NO', 'https://example.com/icon.png'),
    QuickReply::phone(),   // prefilled by Meta
    QuickReply::email(),
])
```

Payloads come back on the `messaging_postbacks` webhook.

## Replying and reacting

```php
->replyTo($mid)                     // thread under a specific message
Meta::facebook()->to($psid)->react($mid, '❤');
Meta::facebook()->to($psid)->unreact($mid);
```

`$mid` is the `mid` from the `messages` webhook. Instagram accepts only `❤` (or the string
`love`) from businesses; anything else is refused locally.

## Sender actions

```php
Meta::facebook()->to($psid)->markSeen();
Meta::facebook()->to($psid)->typing();
Meta::facebook()->to($psid)->text('Here you go')->send();
Meta::facebook()->to($psid)->typingOff();
```

## Messaging type and tags

```php
use AsmaaGamal\MetaMessaging\Enums\{MessagingType, MessageTag};

->messagingType(MessagingType::Response)   // replying inside 24h — the common case
->messagingType(MessagingType::Update)     // proactive, inside 24h
->tag(MessageTag::HumanAgent)              // outside 24h; implies MESSAGE_TAG
```

Only `HUMAN_AGENT` and `CUSTOMER_FEEDBACK` still work. The rest were retired on 2026-04-27 and are
refused locally — see [errors.md](errors.md#retired-message-tags).

## Notification type, personas, metadata

```php
->notificationType(NotificationType::SilentPush)  // REGULAR | SILENT_PUSH | NO_PUSH
->persona($personaId)                             // send as a persona, not the Page
->metadata('order-42')                            // echoed back on message_echoes
```

## Terminal calls

| Call | Behaviour |
| --- | --- |
| `send()` | performs the call, returns `MessageResponse`, throws on failure |
| `sendSafely()` | never throws; failures land in `$response->error()` |
| `queue()` | validates now, dispatches a job, returns `PendingDispatch` |
| `request()` | builds the `MetaRequest` without sending — useful in tests |
| `toPayload()` | the request body as an array |

```php
$response = Meta::facebook()->to($psid)->text('Hi')->send();

$response->messageId();    // 'mid.abc123'
$response->recipientId();  // the scoped ID Meta echoed back
$response->successful;     // true
$response->raw;            // Meta's decoded body
```

## Inspecting without sending

```php
$payload = Meta::facebook()->to($psid)->text('Hi')->toPayload();
// ['recipient' => ['id' => '...'], 'message' => ['text' => 'Hi']]
```

Handy for asserting against Meta's documented shapes, or for debugging what a builder produced.
