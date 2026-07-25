<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Support;

use AsmaaGamal\MetaMessaging\Enums\Channel;
use AsmaaGamal\MetaMessaging\Enums\InstagramLoginType;
use AsmaaGamal\MetaMessaging\Exceptions\InvalidConfigurationException;

/**
 * Resolved credentials for one configured account.
 *
 * Missing credentials are caught here, at resolution time, so the developer sees
 * "the token for account [default] is not set" rather than Meta's opaque
 * "An access token is required to request this resource".
 */
final readonly class AccountConfig
{
    public function __construct(
        public Channel $channel,
        public string $name,
        public string $id,
        public string $token,
        public GraphVersion $version,
        public ?string $appSecret = null,
        public InstagramLoginType $loginType = InstagramLoginType::Instagram,
    ) {}

    /**
     * Build an account from a config array.
     *
     * @param  array<string, mixed>  $config
     * @param  string  $fallbackVersion  the package-level default version
     *
     * @throws InvalidConfigurationException when a required credential is absent
     */
    public static function fromArray(
        Channel $channel,
        string $name,
        array $config,
        string $fallbackVersion,
    ): self {
        $idKey = $channel === Channel::Facebook ? 'page_id' : 'account_id';

        $id = self::requireString($config, $idKey, $channel, $name);
        $token = self::requireString($config, 'token', $channel, $name);

        $version = $config['version'] ?? null;
        $appSecret = $config['app_secret'] ?? null;

        return new self(
            channel: $channel,
            name: $name,
            id: $id,
            token: $token,
            version: GraphVersion::make(is_string($version) && $version !== '' ? $version : $fallbackVersion),
            appSecret: is_string($appSecret) && $appSecret !== '' ? $appSecret : null,
            loginType: self::resolveLoginType($config, $channel, $name),
        );
    }

    /**
     * The Graph host this account talks to.
     */
    public function host(): string
    {
        return $this->channel === Channel::Instagram
            ? $this->loginType->host()
            : 'https://graph.facebook.com';
    }

    /**
     * Base URL including the version segment.
     */
    public function baseUrl(): string
    {
        return $this->host().'/'.$this->version->value;
    }

    /**
     * A copy of this account pinned to a different Graph API version.
     */
    public function withVersion(GraphVersion $version): self
    {
        return new self(
            channel: $this->channel,
            name: $this->name,
            id: $this->id,
            token: $this->token,
            version: $version,
            appSecret: $this->appSecret,
            loginType: $this->loginType,
        );
    }

    /**
     * A copy of this account using a different token, and optionally a
     * different page or Instagram account ID.
     */
    public function withCredentials(string $token, ?string $id = null): self
    {
        return new self(
            channel: $this->channel,
            name: $this->name,
            id: $id ?? $this->id,
            token: $token,
            version: $this->version,
            appSecret: $this->appSecret,
            loginType: $this->loginType,
        );
    }

    /**
     * The appsecret_proof Meta expects when an app secret is configured.
     */
    public function appSecretProof(): ?string
    {
        return $this->appSecret === null
            ? null
            : hash_hmac('sha256', $this->token, $this->appSecret);
    }

    /**
     * The permission scope needed to send messages from this account.
     */
    public function messagingScope(): string
    {
        return $this->channel === Channel::Facebook
            ? 'pages_messaging'
            : $this->loginType->messagingScope();
    }

    /**
     * @param  array<string, mixed>  $config
     *
     * @throws InvalidConfigurationException
     */
    private static function requireString(array $config, string $key, Channel $channel, string $name): string
    {
        $value = $config[$key] ?? null;

        if (! is_string($value) || trim($value) === '') {
            throw InvalidConfigurationException::missingCredential($channel, $name, $key);
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $config
     *
     * @throws InvalidConfigurationException
     */
    private static function resolveLoginType(array $config, Channel $channel, string $name): InstagramLoginType
    {
        if ($channel !== Channel::Instagram) {
            return InstagramLoginType::Instagram;
        }

        $value = $config['login_type'] ?? InstagramLoginType::Instagram->value;

        return InstagramLoginType::tryFrom(is_string($value) ? $value : '')
            ?? throw InvalidConfigurationException::invalidLoginType($name, $value);
    }
}
