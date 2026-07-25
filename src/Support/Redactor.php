<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Support;

/**
 * Strips credentials out of payloads before they reach logs or exceptions.
 *
 * Exceptions from this package carry the request context so failures are easy to
 * debug, and that context ends up in log files and bug reports. Access tokens
 * must never travel with it.
 */
final class Redactor
{
    /**
     * Keys whose values are replaced wholesale.
     *
     * @var array<int, string>
     */
    private const SECRET_KEYS = [
        'access_token',
        'appsecret_proof',
        'app_secret',
        'client_secret',
        'token',
    ];

    private const PLACEHOLDER = '[REDACTED]';

    /**
     * Recursively redact secrets in an array.
     *
     * @param  array<array-key, mixed>  $payload
     * @return array<array-key, mixed>
     */
    public static function scrub(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::SECRET_KEYS, true)) {
                $payload[$key] = self::PLACEHOLDER;

                continue;
            }

            if (is_array($value)) {
                $payload[$key] = self::scrub($value);
            }
        }

        return $payload;
    }

    /**
     * Redact any access_token found in a URL query string.
     */
    public static function scrubUrl(string $url): string
    {
        return (string) preg_replace(
            '/(access_token|appsecret_proof)=[^&]*/i',
            '$1='.self::PLACEHOLDER,
            $url,
        );
    }
}
