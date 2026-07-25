<?php

declare(strict_types=1);

use AsmaaGamal\MetaMessaging\Exceptions\InvalidConfigurationException;
use AsmaaGamal\MetaMessaging\Facades\Meta;

it('uses the configured Graph API version', function (): void {
    config()->set('meta-messaging.version', 'v23.0');

    $fake = Meta::fake();

    Meta::facebook()->to('PSID')->text('Hi')->send();

    expect($fake->lastRequest()?->url())->toContain('/v23.0/');
});

it('lets an account pin its own version', function (): void {
    config()->set('meta-messaging.accounts.facebook.legacy', [
        'page_id' => '444',
        'token' => 'legacy-token',
        'version' => 'v21.0',
    ]);

    $fake = Meta::fake();

    Meta::facebook('legacy')->to('PSID')->text('Hi')->send();

    expect($fake->lastRequest()?->url())->toBe('https://graph.facebook.com/v21.0/444/messages');
});

it('lets a single call override the version', function (): void {
    $fake = Meta::fake();

    Meta::facebook()->usingVersion('v20.0')->to('PSID')->text('Hi')->send();
    Meta::facebook()->to('PSID')->text('Hi')->send();

    expect($fake->requests()[0]->url())->toContain('/v20.0/')
        ->and($fake->requests()[1]->url())->toContain('/v25.0/');
});

it('normalises a version given without the v prefix', function (): void {
    $fake = Meta::fake();

    Meta::facebook()->usingVersion('24.0')->to('PSID')->text('Hi')->send();

    expect($fake->lastRequest()?->url())->toContain('/v24.0/');
});

it('rejects a malformed version with an explanation', function (): void {
    Meta::fake();

    try {
        Meta::facebook()->usingVersion('twenty-five');
        $this->fail('Expected the malformed version to be rejected.');
    } catch (InvalidConfigurationException $e) {
        expect($e->hint())->toContain('Use the form v25.0');
    }
});

it('names the missing credential when an account is incomplete', function (): void {
    config()->set('meta-messaging.accounts.facebook.broken', ['page_id' => '555']);

    Meta::fake();

    try {
        Meta::facebook('broken')->to('PSID')->text('Hi')->send();
        $this->fail('Expected the missing token to be reported.');
    } catch (InvalidConfigurationException $e) {
        expect($e->hint())
            ->toContain('The token for the Facebook Messenger account [broken] is not set')
            ->toContain('meta-messaging.accounts.facebook.broken');
    }
});

it('lists the configured accounts when an unknown one is requested', function (): void {
    Meta::fake();

    try {
        Meta::facebook('does-not-exist')->to('PSID')->text('Hi')->send();
        $this->fail('Expected the unknown account to be reported.');
    } catch (InvalidConfigurationException $e) {
        expect($e->hint())
            ->toContain('no Facebook Messenger account named [does-not-exist]')
            ->toContain('Configured accounts: default');
    }
});

it('rejects an unrecognised Instagram login type', function (): void {
    config()->set('meta-messaging.accounts.instagram.odd', [
        'account_id' => '9',
        'token' => 't',
        'login_type' => 'threads',
    ]);

    Meta::fake();

    try {
        Meta::instagram('odd')->to('IGSID')->text('Hi')->send();
        $this->fail('Expected the login type to be rejected.');
    } catch (InvalidConfigurationException $e) {
        expect($e->hint())->toContain('login_type [threads]');
    }
});

it('swaps in a runtime token for multi-tenant use', function (): void {
    $fake = Meta::fake();

    Meta::facebook()->usingToken('tenant-token', '777')->to('PSID')->text('Hi')->send();

    expect($fake->lastRequest()?->url())->toBe('https://graph.facebook.com/v25.0/777/messages')
        ->and($fake->lastRequest()?->body()['access_token'])->toBe('tenant-token');
});

it('attaches an appsecret proof when an app secret is configured', function (): void {
    config()->set('meta-messaging.accounts.facebook.secured', [
        'page_id' => '888',
        'token' => 'the-token',
        'app_secret' => 'the-secret',
    ]);

    $fake = Meta::fake();

    Meta::facebook('secured')->to('PSID')->text('Hi')->send();

    expect($fake->lastRequest()?->body()['appsecret_proof'])
        ->toBe(hash_hmac('sha256', 'the-token', 'the-secret'));
});

it('omits the appsecret proof when no app secret is set', function (): void {
    $fake = Meta::fake();

    Meta::facebook()->to('PSID')->text('Hi')->send();

    expect($fake->lastRequest()?->body())->not->toHaveKey('appsecret_proof');
});

it('honours the throw config flag', function (): void {
    config()->set('meta-messaging.throw', false);

    Meta::fake()->respondWithError(551, 'Unavailable');

    $response = Meta::facebook()->to('PSID')->text('Hi')->send();

    expect($response->failed())->toBeTrue();
});
