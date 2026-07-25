# Changelog

All notable changes to `asmaa-gamal/laravel-meta-messaging` are documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project
adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-07-25

First release.

### Added

- **Facebook Messenger and Instagram** behind one fluent API, with a `Meta` facade.
- **Every message type** — text, image, audio, video, file, quick replies, replying to a specific
  message, reactions, typing indicators, and mark-as-seen.
- **All six Messenger templates** — generic, button, media, receipt, product, and customer
  feedback — composed from immutable value objects, plus all six button types.
- **Comments** — private replies to commenters, public comment replies, and new top-level
  comments, with the endpoint difference between the two channels handled automatically.
- **Reusable attachment uploads** via the Attachment Upload API.
- **Configurable Graph API version**, globally, per account, or per call.
- **Multiple accounts per channel**, plus `usingToken()` for multi-tenant applications.
- **Both Instagram login flows** — Instagram Login on `graph.instagram.com` and Facebook Login on
  `graph.facebook.com` — with the correct host and permission scope for each.
- **Pre-flight validation** that refuses, before spending an API call: retired message tags,
  features the channel lacks, text past the limit, oversized attachments, too many cards, buttons
  or quick replies, media in a private reply, and non-heart Instagram reactions.
- **Typed exceptions** for every mapped Meta error, each carrying a plain-English hint, the error
  code and subcode, `fbtrace_id`, the endpoint, whether it is retryable, and the request context
  with credentials redacted.
- **A data-driven error catalog** that applications can extend with `ErrorCatalog::extend()`,
  no package release required. Unmapped codes degrade to `MetaApiException` carrying Meta's own
  description.
- **Structured, non-throwing results** through `sendSafely()` and `withoutExceptions()`.
- **Translatable hints** in publishable language files.
- **Queueing** via `->queue()`, with a job that retries throttling and network faults and fails
  permanent errors outright.
- **`MetaMessageSent` and `MetaMessageFailed` events.**
- **`Meta::fake()`**, a recording transport with assertions and canned responses, including error
  responses routed through the real mapping.
- **`appsecret_proof`** attached automatically whenever an app secret is configured.

### Notes

- Meta retired the `CONFIRMED_EVENT_UPDATE`, `ACCOUNT_UPDATE`, and `POST_PURCHASE_UPDATE` message
  tags on 2026-04-27. This package refuses them locally rather than letting Meta answer with an
  unexplained `(#100) Invalid parameter`.
- The 24-hour messaging window is **not** tracked locally. Meta's own response is mapped to
  `MessagingWindowExpiredException` with an explanation.

[Unreleased]: https://github.com/AsmaaGamal30/laravel-meta-messaging/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/AsmaaGamal30/laravel-meta-messaging/releases/tag/v1.0.0
