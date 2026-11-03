<?php

declare(strict_types=1);

use App\Actions\RetractVote;
use App\Enums\FeatureRequestStatus as Status;
use App\Models\FeatureRequest;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Support\Carbon;

test('a user can retract a vote and the count goes down', function () {
    $user = User::factory()->create();
    $featureRequest = FeatureRequest::factory()->create(['votes_count' => 1]);
    Vote::factory()->for($user)->for($featureRequest)->create();

    $updated = app(RetractVote::class)->handle($user, $featureRequest);

    expect($updated->votes_count)->toBe(0)
        ->and($updated->votes()->count())->toBe(0);
});

test('retracting without a vote changes nothing', function () {
    $other = Vote::factory()->create();
    $featureRequest = $other->featureRequest;
    $featureRequest->forceFill(['votes_count' => 1])->save();

    $updated = app(RetractVote::class)->handle(User::factory()->create(), $featureRequest);

    expect($updated->votes_count)->toBe(1)
        ->and($updated->votes()->count())->toBe(1);
});

test('a vote can still be retracted on a finished feature request', function (Status $status) {
    $user = User::factory()->create();
    $featureRequest = FeatureRequest::factory()->create(['status' => $status, 'votes_count' => 1]);
    Vote::factory()->for($user)->for($featureRequest)->create();

    expect(app(RetractVote::class)->handle($user, $featureRequest)->votes_count)->toBe(0);
})->with([Status::Done, Status::Declined]);

test('retracting a vote does not count as an edit of the feature request', function () {
    $user = User::factory()->create();
    $featureRequest = FeatureRequest::factory()->create(['votes_count' => 1, 'updated_at' => '2020-01-01 00:00:00']);
    Vote::factory()->for($user)->for($featureRequest)->create();

    app(RetractVote::class)->handle($user, $featureRequest);

    expect($featureRequest->fresh()?->updated_at)->toEqual(Carbon::parse('2020-01-01 00:00:00'));
});
