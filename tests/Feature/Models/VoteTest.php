<?php

declare(strict_types=1);

use App\Models\FeatureRequest;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\UniqueConstraintViolationException;

test('a vote belongs to a user and a feature request', function () {
    $user = User::factory()->create();
    $featureRequest = FeatureRequest::factory()->create();

    $vote = Vote::factory()->for($user)->for($featureRequest)->create();

    expect($vote->user->is($user))->toBeTrue()
        ->and($vote->featureRequest->is($featureRequest))->toBeTrue()
        ->and($user->votes->sole()->is($vote))->toBeTrue()
        ->and($featureRequest->votes->sole()->is($vote))->toBeTrue();
});

test('a user can vote only once per feature request', function () {
    $vote = Vote::factory()->create();

    expect(fn () => Vote::factory()->create([
        'user_id' => $vote->user_id,
        'feature_request_id' => $vote->feature_request_id,
    ]))->toThrow(UniqueConstraintViolationException::class);
});

test('a user can vote for several feature requests', function () {
    $user = User::factory()->create();

    Vote::factory()->count(2)->for($user)->create();

    expect($user->votes()->count())->toBe(2);
});

test('several users can vote for the same feature request', function () {
    $featureRequest = FeatureRequest::factory()->create();

    Vote::factory()->count(2)->for($featureRequest)->create();

    expect($featureRequest->votes()->count())->toBe(2);
});

test('votes are deleted with their feature request', function () {
    $vote = Vote::factory()->create();

    $vote->featureRequest->delete();

    expect(Vote::query()->count())->toBe(0);
});

test('votes are deleted with their user', function () {
    $vote = Vote::factory()->create();

    $vote->user->delete();

    expect(Vote::query()->count())->toBe(0);
});
