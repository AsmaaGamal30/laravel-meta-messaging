<?php

declare(strict_types=1);

use AsmaaGamal\MetaMessaging\Facades\Meta;

it('sends a private reply through the messages endpoint with a comment_id recipient', function (): void {
    $fake = Meta::fake();

    Meta::facebook()->privateReply('COMMENT_1', 'Sent you the details in DM');

    $request = $fake->lastRequest();

    expect($request?->path)->toBe('1010101/messages')
        ->and($request?->payload)->toBe([
            'recipient' => ['comment_id' => 'COMMENT_1'],
            'message' => ['text' => 'Sent you the details in DM'],
        ]);
});

it('replies publicly to a Facebook comment as a nested comment', function (): void {
    $fake = Meta::fake();

    Meta::facebook()->replyToComment('COMMENT_1', 'Thanks for the kind words!');

    expect($fake->lastRequest()?->path)->toBe('COMMENT_1/comments')
        ->and($fake->lastRequest()?->payload)->toBe(['message' => 'Thanks for the kind words!']);
});

it('leaves a top level comment on a post', function (): void {
    $fake = Meta::fake();

    Meta::facebook()->comment('POST_1', 'Now in stock.');

    expect($fake->lastRequest()?->path)->toBe('POST_1/comments');
});

it('can private reply to whoever wrote a post', function (): void {
    $fake = Meta::fake();

    Meta::facebook()->message()->toPost('POST_1')->text('Hello')->send();

    expect($fake->lastRequest()?->payload['recipient'])->toBe(['post_id' => 'POST_1']);
});

it('reports the comment id in the hint when Meta rejects it', function (): void {
    Meta::fake()->respondWithError(100, 'Invalid comment_id', 2018292);

    $response = Meta::facebook()->withoutExceptions()->privateReply('BAD_COMMENT', 'Hi');

    expect($response->error?->hint)->toContain('BAD_COMMENT');
});
