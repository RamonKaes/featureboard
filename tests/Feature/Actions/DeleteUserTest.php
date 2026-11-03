<?php

declare(strict_types=1);

use App\Actions\DeleteUser;
use App\Models\FeatureRequest;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Support\Carbon;

test('deleting a user lowers the count of the requests they voted for', function () {
    $user = User::factory()->create();
    $voted = FeatureRequest::factory()->create(['votes_count' => 2]);
    Vote::factory()->for($user)->for($voted)->create();
    Vote::factory()->for($voted)->create();

    app(DeleteUser::class)->handle($user);

    expect($voted->fresh()?->votes_count)->toBe(1)
        ->and($voted->votes()->count())->toBe(1)
        ->and(User::query()->whereKey($user->id)->exists())->toBeFalse();
});

test('deleting a user leaves other counts alone', function () {
    $other = FeatureRequest::factory()->create(['votes_count' => 1]);
    Vote::factory()->for($other)->create();

    app(DeleteUser::class)->handle(User::factory()->create());

    expect($other->fresh()?->votes_count)->toBe(1);
});

test('the feature requests of a deleted user stay with their votes', function () {
    $featureRequest = FeatureRequest::factory()->create(['votes_count' => 1]);
    Vote::factory()->for($featureRequest)->create();

    app(DeleteUser::class)->handle($featureRequest->author);

    expect($featureRequest->fresh()?->user_id)->toBeNull()
        ->and($featureRequest->fresh()?->votes_count)->toBe(1);
});

test('losing a vote through account deletion does not count as an edit', function () {
    $user = User::factory()->create();
    $featureRequest = FeatureRequest::factory()->create(['votes_count' => 1, 'updated_at' => '2020-01-01 00:00:00']);
    Vote::factory()->for($user)->for($featureRequest)->create();

    app(DeleteUser::class)->handle($user);

    expect($featureRequest->fresh()?->updated_at)->toEqual(Carbon::parse('2020-01-01 00:00:00'));
});

test('deleting the account in the settings keeps the counts correct', function () {
    $user = User::factory()->create();
    $featureRequest = FeatureRequest::factory()->create(['votes_count' => 1]);
    Vote::factory()->for($user)->for($featureRequest)->create();

    $this->actingAs($user)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect('/');

    expect($featureRequest->fresh()?->votes_count)->toBe(0);
});
