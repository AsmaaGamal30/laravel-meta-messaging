<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Support;

use AsmaaGamal\MetaMessaging\Exceptions\InvalidConfigurationException;
use Stringable;

/**
 * A Graph API version string such as "v25.0".
 *
 * Meta rejects a malformed version with a 404 against an unrelated looking URL,
 * so the format is validated here rather than letting it reach the network.
 */
final readonly class GraphVersion implements Stringable
{
    /**
     * The version Meta shipped most recently at the time of release.
     */
    public const LATEST_KNOWN = 'v25.0';

    private function __construct(
        public string $value,
        public int $major,
        public int $minor,
    ) {}

    /**
     * Build a version from a string, tolerating a missing "v" prefix.
     *
     * @throws InvalidConfigurationException when the string is not a Graph version
     */
    public static function make(string $version): self
    {
        $normalized = str_starts_with($version, 'v') ? $version : 'v'.$version;

        if (preg_match('/^v(\d+)\.(\d+)$/', $normalized, $matches) !== 1) {
            throw InvalidConfigurationException::invalidVersion($version);
        }

        return new self($normalized, (int) $matches[1], (int) $matches[2]);
    }

    /**
     * The most recent version known to this release of the package.
     */
    public static function latest(): self
    {
        return self::make(self::LATEST_KNOWN);
    }

    /**
     * Whether this version is at least the given one.
     */
    public function atLeast(string $version): bool
    {
        $other = self::make($version);

        return [$this->major, $this->minor] >= [$other->major, $other->minor];
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
