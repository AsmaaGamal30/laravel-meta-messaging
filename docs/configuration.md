# Configuration

```bash
php artisan vendor:publish --tag=meta-messaging-config
```

## Graph API version

```php
'version' => env('META_GRAPH_VERSION', 'v25.0'),
```

Resolution order, most specific first:

1. `->usingVersion('v23.0')` on the call
2. `version` on the account
3. `meta-messaging.version`

A malformed version is rejected immediately with an explanation, rather than becoming a 404
against a URL that looks fine.

Meta supports roughly the two most recent years of versions. Check the
[changelog](https://developers.facebook.com/docs/graph-api/changelog) before pinning an old one.

## Accounts

Any number, per channel, keyed by name:

```php
'accounts' => [
    'facebook' => [
        'default' => [
            'page_id'    => env('META_FACEBOOK_PAGE_ID'),
            'token'      => env('META_FACEBOOK_PAGE_TOKEN'),
            'app_secret' => env('META_APP_SECRET'),
        ],
        'support' => [
            'page_id' => '...',
            'token'   => '...',
            'version' => 'v23.0',
        ],
    ],

    'instagram' => [
        'default' => [
            'account_id' => env('META_INSTAGRAM_ACCOUNT_ID'),
            'token'      => env('META_INSTAGRAM_TOKEN'),
            'login_type' => 'instagram',
        ],
    ],
],
```

```php
Meta::facebook();           // the 'default' account
Meta::facebook('support');  // a named one
```

An unknown name lists what *is* configured; a missing credential names the exact key and where to
put it.

### Instagram login types

Instagram exposes messaging through two flows on different hosts:

| `login_type` | Host | Needs a linked Page? | Scope |
| --- | --- | --- | --- |
| `instagram` | `graph.instagram.com` | no | `instagram_business_manage_messages` |
| `facebook` | `graph.facebook.com` | yes | `instagram_manage_messages` |

`instagram` is Meta's recommended flow. Set it correctly — the package uses it to pick the host
and to name the right scope in permission errors.

### Runtime credentials

For multi-tenant apps that store a token per customer:

```php
Meta::facebook()->usingToken($tenant->page_token, $tenant->page_id)
    ->to($psid)->text('Hi')->send();
```

## App secret

Set `app_secret` and an `appsecret_proof` is attached to every request automatically. Meta
recommends this for server-side calls; it is omitted when no secret is configured.

## Error behaviour

```php
'throw'    => env('META_THROW_ON_ERROR', true),
'validate' => env('META_VALIDATE', true),
```

`throw` decides whether `send()` raises or returns a failed response. `sendSafely()` ignores it
and never throws. Per-channel: `Meta::facebook()->withoutExceptions()`.

`validate` toggles the pre-flight checks. Leave it on — it costs nothing and saves API calls.

## HTTP

```php
'http' => [
    'timeout'         => 30,
    'connect_timeout' => 10,
    'retries'         => 2,
    'retry_delay'     => 500,
],
```

Retries apply to connection-level faults only. A 4xx from Meta is never blindly repeated — a
blocked recipient does not become unblocked on the second attempt.

## Queue

```php
'queue' => [
    'connection' => env('META_QUEUE_CONNECTION'),
    'queue'      => env('META_QUEUE'),
    'tries'      => 3,
],
```

Used by `->queue()`. Null falls back to your application defaults.

## Logging

```php
'logging' => [
    'enabled' => env('META_LOG', false),
    'channel' => env('META_LOG_CHANNEL'),
],
```

Logs each request and response at debug level. Tokens and appsecret proofs are always redacted.

## Required permissions

**Messenger** — `pages_messaging`, plus `pages_manage_engagement` for comment replies and
`pages_read_engagement` to read them. Private replies also need `pages_messaging` with the Human
Agent feature.

**Instagram** — `instagram_business_manage_messages` (Instagram Login) or
`instagram_manage_messages` (Facebook Login), plus `instagram_manage_comments` for comment
features.

All need Advanced Access to reach people without a role on your app. Until then you can only
message admins, developers, and testers — and the package tells you so when Meta refuses.
