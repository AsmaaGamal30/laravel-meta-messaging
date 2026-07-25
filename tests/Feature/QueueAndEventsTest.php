<?php

declare(strict_types=1);

use AsmaaGamal\MetaMessaging\Contracts\Transport;
use AsmaaGamal\MetaMessaging\Events\MetaMessageFailed;
use AsmaaGamal\MetaMessaging\Events\MetaMessageSent;
use AsmaaGamal\MetaMessaging\Exceptions\MessageValidationException;
use AsmaaGamal\MetaMessaging\Exceptions\RateLimitExceededException;
use AsmaaGamal\MetaMessaging\Facades\Meta;
use AsmaaGamal\MetaMessaging\Jobs\SendMetaMessageJob;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

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
