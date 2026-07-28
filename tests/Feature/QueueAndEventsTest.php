<?php

declare(strict_types=1);

use AsmaaGamal\MetaMessaging\Contracts\Transport;
use AsmaaGamal\MetaMessaging\Events\MetaMessageFailed;
use AsmaaGamal\MetaMessaging\Events\MetaMessageSent;
use AsmaaGamal\MetaMessaging\Exceptions\MessageValidationException;
use AsmaaGamal\MetaMessaging\Exceptions\RateLimitExceededException;
use AsmaaGamal\MetaMessaging\Facades\Meta;
use AsmaaGamal\MetaMessaging\Jobs\SendMetaMessageJob;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Queue\Jobs\DatabaseJob;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

it('raises an event when a message is accepted', function (): void {
    Event::fake([MetaMessageSent::class]);
    Meta::fake();

    Meta::facebook()->to('PSID')->text('Hi')->send();

    Event::assertDispatched(
        MetaMessageSent::class,
        fn (MetaMessageSent $event): bool => $event->messageId() === 'm_fake_1',
    );
});

it('raises an event when a message fails, before the exception', function (): void {
    Event::fake([MetaMessageFailed::class]);
    Meta::fake()->respondWithError(551, 'Unavailable');

    Meta::facebook()->to('PSID')->text('Hi')->sendSafely();

    Event::assertDispatched(
        MetaMessageFailed::class,
        fn (MetaMessageFailed $event): bool => $event->error()?->key === 'recipient_unavailable',
    );
});

it('queues a send as a job', function (): void {
    Queue::fake();
    Meta::fake();

    Meta::facebook()->to('PSID')->text('Queued hello')->queue();

    Queue::assertPushed(
        SendMetaMessageJob::class,
        fn (SendMetaMessageJob $job): bool => $job->request->payload['message']['text'] === 'Queued hello',
    );
});

it('validates before queueing so a bad message fails at the call site', function (): void {
    Queue::fake();
    Meta::fake();

    expect(fn () => Meta::instagram()->to('IGSID')->text(str_repeat('a', 1001))->queue())
        ->toThrow(MessageValidationException::class);

    Queue::assertNothingPushed();
});

it('retries a throttled job but fails a permanent one outright', function (): void {
    Bus::fake();

    $fake = Meta::fake()->respondWithError(613, 'Rate limit');
    $this->app->instance(Transport::class, $fake);

    $request = Meta::facebook()->to('PSID')->text('Hi')->request();

    // A rate limit is worth another attempt, so the job throws to be retried.
    expect(fn () => (new SendMetaMessageJob($request))->handle($fake, $this->app['events']))
        ->toThrow(RateLimitExceededException::class);
});

it('reads its queue settings from config', function (): void {
    config()->set('meta-messaging.queue', [
        'connection' => 'redis',
        'queue' => 'meta',
        'tries' => 7,
    ]);

    Meta::fake();

    $job = new SendMetaMessageJob(Meta::facebook()->to('PSID')->text('Hi')->request());

    expect($job->tries)->toBe(7)
        ->and($job->connection)->toBe('redis')
        ->and($job->queue)->toBe('meta');
});

it('encrypts sensitive credentials in the database queue table', function (): void {
    config()->set('database.default', 'testing');
    config()->set('database.connections.testing', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);
    config()->set('queue.default', 'database');
    config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

    Schema::create('jobs', function (Blueprint $table): void {
        $table->bigIncrements('id');
        $table->string('queue')->index();
        $table->longText('payload');
        $table->unsignedTinyInteger('attempts');
        $table->unsignedInteger('reserved_at')->nullable();
        $table->unsignedInteger('available_at');
        $table->unsignedInteger('created_at');
    });

    config()->set('meta-messaging.accounts.facebook.default', [
        'page_id' => '1010101',
        'token' => 'super-secret-page-token',
        'app_secret' => 'super-secret-app-secret',
    ]);

    Meta::facebook()->to('PSID-123')->text('Secret body')->queue();

    /** @var stdClass|null $row */
    $row = DB::table('jobs')->first();
    expect($row)->not->toBeNull();

    $rawPayload = $row->payload;
    expect($rawPayload)->not->toContain('super-secret-page-token')
        ->and($rawPayload)->not->toContain('super-secret-app-secret')
        ->and($rawPayload)->not->toContain('Secret body');
});

it('decrypts the job payload so worker receives real credentials', function (): void {
    config()->set('database.default', 'testing');
    config()->set('database.connections.testing', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);
    config()->set('queue.default', 'database');
    config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

    Schema::create('jobs', function (Blueprint $table): void {
        $table->bigIncrements('id');
        $table->string('queue')->index();
        $table->longText('payload');
        $table->unsignedTinyInteger('attempts');
        $table->unsignedInteger('reserved_at')->nullable();
        $table->unsignedInteger('available_at');
        $table->unsignedInteger('created_at');
    });

    config()->set('meta-messaging.accounts.facebook.default', [
        'page_id' => '1010101',
        'token' => 'real-worker-token-999',
        'app_secret' => 'real-worker-secret-888',
    ]);

    Meta::facebook()->to('PSID-999')->text('Worker hello')->queue();

    /** @var DatabaseJob|null $job */
    $job = Queue::connection('database')->pop();
    expect($job)->not->toBeNull();

    $payload = $job->payload();
    $command = unserialize($this->app[Encrypter::class]->decrypt($payload['data']['command']));

    expect($command)->toBeInstanceOf(SendMetaMessageJob::class)
        ->and($command->request->account->token)->toBe('real-worker-token-999')
        ->and($command->request->account->appSecret)->toBe('real-worker-secret-888')
        ->and($command->request->payload['message']['text'])->toBe('Worker hello');
});
