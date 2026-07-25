<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging;

use AsmaaGamal\MetaMessaging\Channels\FacebookChannel;
use AsmaaGamal\MetaMessaging\Channels\InstagramChannel;
use AsmaaGamal\MetaMessaging\Channels\MessagingChannel;
use AsmaaGamal\MetaMessaging\Contracts\Transport;
use AsmaaGamal\MetaMessaging\Enums\Channel;
use AsmaaGamal\MetaMessaging\Exceptions\InvalidConfigurationException;
use AsmaaGamal\MetaMessaging\Support\AccountConfig;
use AsmaaGamal\MetaMessaging\Transport\FakeTransport;
use AsmaaGamal\MetaMessaging\Validation\ValidationPipeline;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * The package's entry point, reached through the Meta facade.
 *
 * Resolves a named account into a configured channel, and owns the transport
 * swap that makes Meta::fake() work.
 */
final class MetaManager
{
    /** @var array<string, MessagingChannel> */
    private array $resolved = [];

    private ?FakeTransport $fake = null;

    /**
     * @param  array<string, mixed>  $config  the meta-messaging config array
     */
    public function __construct(
        private array $config,
        private Transport $transport,
        private readonly ValidationPipeline $validator,
        private readonly ?Dispatcher $events = null,
    ) {}

    /**
     * A Facebook Messenger channel for the given account.
     */
    public function facebook(?string $account = null): FacebookChannel
    {
        /** @var FacebookChannel */
        return $this->channel(Channel::Facebook, $account);
    }

    /**
     * An Instagram channel for the given account.
     */
    public function instagram(?string $account = null): InstagramChannel
    {
        /** @var InstagramChannel */
        return $this->channel(Channel::Instagram, $account);
    }

    /**
     * Resolve any channel by enum.
     */
    public function channel(Channel $channel, ?string $account = null): MessagingChannel
    {
        $name = $account ?? $this->defaultAccount($channel);
        $key = $channel->value.':'.$name;

        return $this->resolved[$key] ??= $this->build($channel, $name);
    }

    /**
     * Swap in a recording transport for tests.
     *
     * Nothing leaves the process afterwards; assertions go through the returned
     * FakeTransport, or through Meta::assertSent().
     */
    public function fake(): FakeTransport
    {
        $this->fake = new FakeTransport;
        $this->transport = $this->fake;
        $this->resolved = [];

        return $this->fake;
    }

    /**
     * The active fake, if fake() has been called.
     */
    public function faked(): ?FakeTransport
    {
        return $this->fake;
    }

    /**
     * Assert a matching request was sent. Requires fake() first.
     */
    public function assertSent(?\Closure $callback = null): void
    {
        $this->requireFake()->assertSent($callback);
    }

    /**
     * Assert no matching request was sent. Requires fake() first.
     */
    public function assertNotSent(?\Closure $callback = null): void
    {
        $this->requireFake()->assertNotSent($callback);
    }

    /**
     * Assert nothing at all was sent. Requires fake() first.
     */
    public function assertNothingSent(): void
    {
        $this->requireFake()->assertNothingSent();
    }

    /**
     * The Graph API version used when an account does not override it.
     */
    public function version(): string
    {
        $version = $this->config['version'] ?? null;

        return is_string($version) && $version !== '' ? $version : Support\GraphVersion::LATEST_KNOWN;
    }

    private function build(Channel $channel, string $name): MessagingChannel
    {
        $account = AccountConfig::fromArray(
            channel: $channel,
            name: $name,
            config: $this->accountConfig($channel, $name),
            fallbackVersion: $this->version(),
        );

        $throw = (bool) ($this->config['throw'] ?? true);

        return $channel === Channel::Facebook
            ? new FacebookChannel($account, $this->transport, $this->validator, $this->events, $throw)
            : new InstagramChannel($account, $this->transport, $this->validator, $this->events, $throw);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws InvalidConfigurationException when the account is not configured
     */
    private function accountConfig(Channel $channel, string $name): array
    {
        /** @var array<string, mixed> $accounts */
        $accounts = (array) ($this->config['accounts'][$channel->value] ?? []);

        if (! isset($accounts[$name]) || ! is_array($accounts[$name])) {
            throw InvalidConfigurationException::unknownAccount(
                $channel,
                $name,
                array_map(strval(...), array_keys($accounts)),
            );
        }

        return $accounts[$name];
    }

    private function defaultAccount(Channel $channel): string
    {
        $default = $this->config['defaults'][$channel->value] ?? 'default';

        return is_string($default) && $default !== '' ? $default : 'default';
    }

    private function requireFake(): FakeTransport
    {
        return $this->fake ?? throw new \LogicException(
            'Call Meta::fake() before asserting on sent messages.',
        );
    }
}
