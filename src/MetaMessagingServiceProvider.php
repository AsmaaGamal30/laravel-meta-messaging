<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging;

use AsmaaGamal\MetaMessaging\Contracts\Transport;
use AsmaaGamal\MetaMessaging\Transport\HttpTransport;
use AsmaaGamal\MetaMessaging\Validation\ValidationPipeline;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Log\LogManager;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

final class MetaMessagingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/meta-messaging.php', 'meta-messaging');

        $this->app->singleton(ValidationPipeline::class, fn (): ValidationPipeline => new ValidationPipeline(
            enabled: (bool) config('meta-messaging.validate', true),
        ));

        $this->app->singleton(Transport::class, fn ($app): Transport => new HttpTransport(
            http: $app->make(HttpFactory::class),
            options: (array) config('meta-messaging.http', []),
            logger: $this->resolveLogger(),
        ));

        $this->app->singleton(MetaManager::class, fn ($app): MetaManager => new MetaManager(
            config: (array) config('meta-messaging', []),
            transport: $app->make(Transport::class),
            validator: $app->make(ValidationPipeline::class),
            events: $app->bound(Dispatcher::class) ? $app->make(Dispatcher::class) : null,
        ));
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'meta-messaging');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/meta-messaging.php' => config_path('meta-messaging.php'),
            ], 'meta-messaging-config');

            $this->publishes([
                __DIR__.'/../lang' => $this->app->langPath('vendor/meta-messaging'),
            ], 'meta-messaging-lang');
        }
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [MetaManager::class, Transport::class, ValidationPipeline::class];
    }

    /**
     * The logger request and response debugging is written to, if enabled.
     *
     * Resolves the concrete LogManager rather than the PSR interface, because
     * picking a named channel is a Laravel concern the interface does not cover.
     */
    private function resolveLogger(): ?LoggerInterface
    {
        if (! (bool) config('meta-messaging.logging.enabled', false)) {
            return null;
        }

        $logs = $this->app->make(LogManager::class);

        if (! $logs instanceof LogManager) {
            return null;
        }

        $channel = config('meta-messaging.logging.channel');

        return is_string($channel) && $channel !== ''
            ? $logs->channel($channel)
            : $logs->driver();
    }
}
