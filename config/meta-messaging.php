<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Graph API Version
    |--------------------------------------------------------------------------
    |
    | The Meta Graph API version used for every request unless an account or an
    | individual call overrides it. Meta supports roughly the two most recent
    | years of versions; check the changelog before pinning an older one:
    | https://developers.facebook.com/docs/graph-api/changelog
    |
    | Override per call with ->usingVersion('v23.0').
    |
    */

    'version' => env('META_GRAPH_VERSION', 'v25.0'),

    /*
    |--------------------------------------------------------------------------
    | Default Accounts
    |--------------------------------------------------------------------------
    |
    | Which named account each channel uses when you call Meta::facebook() or
    | Meta::instagram() without arguments.
    |
    */

    'defaults' => [
        'facebook' => env('META_FACEBOOK_ACCOUNT', 'default'),
        'instagram' => env('META_INSTAGRAM_ACCOUNT', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Accounts
    |--------------------------------------------------------------------------
    |
    | Credentials per channel, keyed by account name. Add as many as you need
    | and select one with Meta::facebook('second_page').
    |
    | facebook.page_id   The Facebook Page ID that owns the conversation.
    | facebook.token     A Page access token with pages_messaging.
    |
    | instagram.account_id  The Instagram professional account ID.
    | instagram.token       An Instagram User (Instagram Login) or Page
    |                       (Facebook Login) access token.
    | instagram.login_type  'instagram' targets graph.instagram.com and requires
    |                       instagram_business_manage_messages.
    |                       'facebook' targets graph.facebook.com and requires
    |                       the account be linked to a Page, plus
    |                       instagram_manage_messages.
    |
    | app_secret is optional. When present, an appsecret_proof is attached to
    | every request, which Meta recommends for server-side calls.
    |
    */

    'accounts' => [

        'facebook' => [
            'default' => [
                'page_id' => env('META_FACEBOOK_PAGE_ID'),
                'token' => env('META_FACEBOOK_PAGE_TOKEN'),
                'app_secret' => env('META_APP_SECRET'),
                'version' => env('META_FACEBOOK_GRAPH_VERSION'),
            ],
        ],

        'instagram' => [
            'default' => [
                'account_id' => env('META_INSTAGRAM_ACCOUNT_ID'),
                'token' => env('META_INSTAGRAM_TOKEN'),
                'app_secret' => env('META_APP_SECRET'),
                'login_type' => env('META_INSTAGRAM_LOGIN_TYPE', 'instagram'),
                'version' => env('META_INSTAGRAM_GRAPH_VERSION'),
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    |
    | throw    When true, send() raises a typed MetaMessagingException on
    |          failure. When false it returns a failed MessageResponse instead.
    |          sendSafely() never throws regardless of this setting.
    |
    | validate Run the pre-flight validators (capability, size limits,
    |          deprecated message tags, reaction rules) before spending an API
    |          call. Leave this on unless you are debugging the package itself.
    |
    */

    'throw' => env('META_THROW_ON_ERROR', true),

    'validate' => env('META_VALIDATE', true),

    /*
    |--------------------------------------------------------------------------
    | HTTP
    |--------------------------------------------------------------------------
    |
    | retry_on lists the exception classes worth retrying. Rate limits and
    | transient network faults are retried; a blocked recipient never is.
    |
    */

    'http' => [
        'timeout' => env('META_HTTP_TIMEOUT', 30),
        'connect_timeout' => env('META_HTTP_CONNECT_TIMEOUT', 10),
        'retries' => env('META_HTTP_RETRIES', 2),
        'retry_delay' => env('META_HTTP_RETRY_DELAY', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | Used by ->queue(). Null uses the application defaults.
    |
    */

    'queue' => [
        'connection' => env('META_QUEUE_CONNECTION'),
        'queue' => env('META_QUEUE'),
        'tries' => env('META_QUEUE_TRIES', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Log every request and response. Access tokens and appsecret proofs are
    | always redacted before anything is written.
    |
    */

    'logging' => [
        'enabled' => env('META_LOG', false),
        'channel' => env('META_LOG_CHANNEL'),
    ],

];
