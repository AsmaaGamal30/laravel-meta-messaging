<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Facades;

use AsmaaGamal\MetaMessaging\MetaManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \AsmaaGamal\MetaMessaging\Channels\FacebookChannel facebook(?string $account = null)
 * @method static \AsmaaGamal\MetaMessaging\Channels\InstagramChannel instagram(?string $account = null)
 * @method static \AsmaaGamal\MetaMessaging\Channels\MessagingChannel channel(\AsmaaGamal\MetaMessaging\Enums\Channel $channel, ?string $account = null)
 * @method static \AsmaaGamal\MetaMessaging\Transport\FakeTransport fake()
 * @method static \AsmaaGamal\MetaMessaging\Transport\FakeTransport|null faked()
 * @method static void assertSent(?\Closure $callback = null)
 * @method static void assertNotSent(?\Closure $callback = null)
 * @method static void assertNothingSent()
 * @method static string version()
 *
 * @see MetaManager
 */
final class Meta extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MetaManager::class;
    }
}
