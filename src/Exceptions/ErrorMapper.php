<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Exceptions;

use AsmaaGamal\MetaMessaging\Responses\MetaError;
use AsmaaGamal\MetaMessaging\Support\Hint;
use AsmaaGamal\MetaMessaging\Transport\MetaRequest;

/**
 * Turns a Graph API failure into a structured MetaError, then into the typed
 * exception the catalog nominates.
 */
final class ErrorMapper
{
    /**
     * Build an error from Meta's response body.
     *
     * @param  array<string, mixed>  $body  decoded response
     */
    public static function fromBody(array $body, int $status, MetaRequest $request): MetaError
    {
        /** @var array<string, mixed> $error */
        $error = is_array($body['error'] ?? null) ? $body['error'] : [];

        $code = self::intOrNull($error['code'] ?? null);
        $subcode = self::intOrNull($error['error_subcode'] ?? null);
        $message = self::stringOrNull($error['message'] ?? null) ?? 'Meta returned HTTP '.$status.' with no error description.';

        $rule = ErrorCatalog::resolve($code, $subcode, $message);

        return new MetaError(
            key: $rule['key'],
            message: $message,
            hint: Hint::get($rule['key'], self::replacements($request, $error)),
            channel: $request->channel(),
            code: $code,
            subcode: $subcode,
            type: self::stringOrNull($error['type'] ?? null),
            userTitle: self::stringOrNull($error['error_user_title'] ?? null),
            userMessage: self::stringOrNull($error['error_user_msg'] ?? null),
            traceId: self::stringOrNull($error['fbtrace_id'] ?? null),
            status: $status,
            endpoint: $request->path,
            retryable: $rule['retryable'],
            context: $request->toArray(),
            exceptionClass: $rule['exception'],
        );
    }

    /**
     * Raise an error as the exception it was mapped to.
     *
     * The class is decided when the error is built and carried on it, so errors
     * with no Meta code — a connection failure, an unreadable response — keep
     * their own type instead of collapsing into the generic API exception.
     */
    public static function toException(MetaError $error): MetaMessagingException
    {
        /** @var class-string<MetaMessagingException> $class */
        $class = $error->exceptionClass
            ?? ErrorCatalog::resolve($error->code, $error->subcode, $error->message)['exception'];

        return new $class($error);
    }

    /**
     * The request never completed.
     */
    public static function transport(string $reason, MetaRequest $request): MetaError
    {
        return new MetaError(
            key: 'transport_failure',
            message: $reason,
            hint: Hint::get('transport_failure', ['reason' => $reason]),
            channel: $request->channel(),
            endpoint: $request->path,
            retryable: true,
            context: $request->toArray(),
            exceptionClass: TransportException::class,
        );
    }

    /**
     * The response arrived but was not JSON.
     */
    public static function malformed(string $raw, int $status, MetaRequest $request): MetaError
    {
        return new MetaError(
            key: 'malformed_response',
            message: 'Meta returned a non-JSON response.',
            hint: Hint::get('malformed_response'),
            channel: $request->channel(),
            status: $status,
            endpoint: $request->path,
            retryable: $status >= 500,
            context: [...$request->toArray(), 'body' => mb_substr($raw, 0, 500)],
            exceptionClass: TransportException::class,
        );
    }

    /**
     * Values available to hint placeholders. Unused keys are simply ignored.
     *
     * @param  array<string, mixed>  $error
     * @return array<string, string|int>
     */
    private static function replacements(MetaRequest $request, array $error): array
    {
        /** @var array<string, mixed> $recipient */
        $recipient = is_array($request->payload['recipient'] ?? null) ? $request->payload['recipient'] : [];

        $commentId = self::stringOrNull($recipient['comment_id'] ?? null)
            ?? self::commentIdFromPath($request->path)
            ?? 'unknown';

        return [
            'channel' => $request->channel()->label(),
            'scope' => $request->account->messagingScope(),
            'recipient' => self::stringOrNull($recipient['id'] ?? null) ?? 'unknown',
            'comment' => $commentId,
            'object' => explode('/', trim($request->path, '/'))[0] ?? 'unknown',
            'reason' => self::stringOrNull($error['message'] ?? null) ?? 'unknown',
        ];
    }

    /**
     * Comment endpoints look like "{comment-id}/comments" or "{id}/replies".
     */
    private static function commentIdFromPath(string $path): ?string
    {
        $segments = explode('/', trim($path, '/'));

        return count($segments) === 2 && in_array($segments[1], ['comments', 'replies', 'private_replies'], true)
            ? $segments[0]
            : null;
    }

    private static function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
