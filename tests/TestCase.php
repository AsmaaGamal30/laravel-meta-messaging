<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Tests;

use AsmaaGamal\MetaMessaging\Exceptions\ErrorCatalog;
use AsmaaGamal\MetaMessaging\Facades\Meta;
use AsmaaGamal\MetaMessaging\MetaMessagingServiceProvider;
use AsmaaGamal\MetaMessaging\Support\Hint;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        ErrorCatalog::flush();
        Hint::flush();
    }

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [MetaMessagingServiceProvider::class];
    }

    /**
     * @return array<string, class-string>
     */
    protected function getPackageAliases($app): array
    {
        return ['Meta' => Meta::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('meta-messaging.version', 'v25.0');

        $app['config']->set('meta-messaging.accounts.facebook.default', [
            'page_id' => '1010101',
            'token' => 'page-token',
        ]);

        $app['config']->set('meta-messaging.accounts.instagram.default', [
            'account_id' => '2020202',
            'token' => 'ig-token',
            'login_type' => 'instagram',
        ]);
    }
}
