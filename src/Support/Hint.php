<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Support;

/**
 * Resolves the plain-English hint attached to every error.
 *
 * Hints live in lang/en/errors.php so applications can translate or reword them
 * by publishing the language files. When no translator is bound — inside a unit
 * test, or when the package is used outside a Laravel container — the shipped
 * English file is read directly, so a hint is never missing.
 */
final class Hint
{
    public const NAMESPACE = 'meta-messaging';

    /** @var array<string, string>|null */
    private static ?array $fallbackLines = null;

    /**
     * Resolve a hint by key, substituting :placeholders.
     *
     * @param  array<string, string|int>  $replace
     */
    public static function get(string $key, array $replace = []): string
    {
        $namespaced = self::NAMESPACE.'::errors.'.$key;

        if (self::translatorAvailable()) {
            $line = trans($namespaced, $replace);

            if (is_string($line) && $line !== $namespaced) {
                return $line;
            }
        }

        return self::substitute(self::fallbackLines()[$key] ?? $key, $replace);
    }

    /**
     * Forget the cached fallback lines. Only useful in tests.
     */
    public static function flush(): void
    {
        self::$fallbackLines = null;
    }

    private static function translatorAvailable(): bool
    {
        return function_exists('app')
            && function_exists('trans')
            && app()->bound('translator');
    }

    /**
     * @return array<string, string>
     */
    private static function fallbackLines(): array
    {
        if (self::$fallbackLines === null) {
            $path = dirname(__DIR__, 2).'/lang/en/errors.php';
            $lines = is_file($path) ? require $path : [];

            self::$fallbackLines = is_array($lines) ? $lines : [];
        }

        return self::$fallbackLines;
    }

    /**
     * @param  array<string, string|int>  $replace
     */
    private static function substitute(string $line, array $replace): string
    {
        foreach ($replace as $key => $value) {
            $line = str_replace(':'.$key, (string) $value, $line);
        }

        return $line;
    }
}
