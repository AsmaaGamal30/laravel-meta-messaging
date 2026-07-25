<?php

declare(strict_types=1);

arch('nothing is left half-debugged')
    ->expect(['dd', 'dump', 'var_dump', 'ray', 'die'])
    ->not->toBeUsed();

arch('every source file declares strict types')
    ->expect('AsmaaGamal\MetaMessaging')
    ->toUseStrictTypes();

arch('contracts are interfaces')
    ->expect('AsmaaGamal\MetaMessaging\Contracts')
    ->toBeInterfaces();

arch('enums stay enums')
    ->expect('AsmaaGamal\MetaMessaging\Enums')
    ->toBeEnums();

arch('every exception extends the package base')
    ->expect('AsmaaGamal\MetaMessaging\Exceptions')
    ->classes()
    ->toExtend('AsmaaGamal\MetaMessaging\Exceptions\MetaMessagingException')
    ->ignoring([
        'AsmaaGamal\MetaMessaging\Exceptions\ErrorCatalog',
        'AsmaaGamal\MetaMessaging\Exceptions\ErrorMapper',
        'AsmaaGamal\MetaMessaging\Exceptions\MetaMessagingException',
    ]);

arch('value objects are immutable')
    ->expect([
        'AsmaaGamal\MetaMessaging\Messages\Content',
        'AsmaaGamal\MetaMessaging\Messages\Templates',
        'AsmaaGamal\MetaMessaging\Messages\Buttons',
        'AsmaaGamal\MetaMessaging\Responses',
    ])
    ->toBeReadonly();

arch('templates and buttons never reach for the network or the container')
    ->expect([
        'AsmaaGamal\MetaMessaging\Messages\Templates',
        'AsmaaGamal\MetaMessaging\Messages\Buttons',
    ])
    ->not->toUse([
        'Illuminate\Support\Facades\Http',
        'Illuminate\Http\Client\Factory',
        'AsmaaGamal\MetaMessaging\Contracts\Transport',
    ]);

// The provider names the HTTP client only to wire it into the container; the
// transport layer is the only place that actually calls it.
arch('only the transport layer talks to the HTTP client')
    ->expect('Illuminate\Http\Client\Factory')
    ->toOnlyBeUsedIn([
        'AsmaaGamal\MetaMessaging\Transport',
        'AsmaaGamal\MetaMessaging\MetaMessagingServiceProvider',
    ]);
