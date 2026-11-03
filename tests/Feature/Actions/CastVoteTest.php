<?php

declare(strict_types=1);

use App\Actions\CastVote;
use App\Enums\FeatureRequestStatus as Status;
use App\Models\FeatureRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;

test('a user can vote and the count goes up', function () {
    $user = User::factory()->create();
    $featureRequest = FeatureRequest::factory()->create();

    $updated = app(CastVote::class)->handle($user, $featureRequest);

    expect($updated->votes_count)->toBe(1)
        ->and($updated->votes()->where('user_id', $user->id)->count())->toBe(1);
});

test('voting twice counts only once', function () {
    $user = User::factory()->create();
    $featureRequest = FeatureRequest::factory()->create();

    app(CastVote::class)->handle($user, $featureRequest);
    $updated = app(CastVote::class)->handle($user, $featureRequest);

    expect($updated->votes_count)->toBe(1)
        ->and($updated->votes()->count())->toBe(1);
});

test('votes from different users add up', function () {
    $featureRequest = FeatureRequest::factory()->create();

    app(CastVote::class)->handle(User::factory()->create(), $featureRequest);
    $updated = app(CastVote::class)->handle(User::factory()->create(), $featureRequest);

    expect($updated->votes_count)->toBe(2);
});

test('authors cannot vote for their own feature request', function () {
    $featureRequest = FeatureRequest::factory()->create();

    expect(fn () => app(CastVote::class)->handle($featureRequest->author, $featureRequest))
        ->toThrow(AuthorizationException::class);

    expect($featureRequest->votes()->count())->toBe(0)
        ->and($featureRequest->fresh()?->votes_count)->toBe(0);
});

test('finished feature requests cannot receive votes', function (Status $status) {
    $featureRequest = FeatureRequest::factory()->create(['status' => $status]);

    expect(fn () => app(CastVote::class)->handle(User::factory()->create(), $featureRequest))
        ->toThrow(AuthorizationException::class);

    expect($featureRequest->votes()->count())->toBe(0);
})->with([Status::Done, Status::Declined]);

test('open feature requests can receive votes', function (Status $status) {
    $featureRequest = FeatureRequest::factory()->create(['status' => $status]);

    expect(app(CastVote::class)->handle(User::factory()->create(), $featureRequest)->votes_count)->toBe(1);
})->with([Status::New, Status::Planned, Status::InProgress]);

test('the rules use the current status, not a stale model', function () {
    $stale = FeatureRequest::factory()->create();
    FeatureRequest::query()->whereKey($stale->id)->update(['status' => Status::Declined]);

    expect(fn () => app(CastVote::class)->handle(User::factory()->create(), $stale))
        ->toThrow(AuthorizationException::class);
});

test('a vote does not count as an edit of the feature request', function () {
    $featureRequest = FeatureRequest::factory()->create(['updated_at' => '2020-01-01 00:00:00']);

    app(CastVote::class)->handle(User::factory()->create(), $featureRequest);

    expect($featureRequest->fresh()?->updated_at)->toEqual(Carbon::parse('2020-01-01 00:00:00'));
});
