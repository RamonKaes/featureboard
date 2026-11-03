<?php

declare(strict_types=1);

use App\Actions\CastVote;
use App\Actions\ChangeStatus;
use App\Enums\FeatureRequestStatus;
use App\Models\FeatureRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;

/*
 * These tests commit real rows, because other processes cannot see an open test transaction.
 * The tasks are built in global functions, because closures created inside a Pest test
 * carry its generated class as scope, which the child process cannot load.
 */
beforeEach(fn () => $this->artisan('migrate'));

afterEach(fn () => DB::statement('TRUNCATE users, products RESTART IDENTITY CASCADE'));

/**
 * Build a task that votes at a shared start time and reports the outcome.
 */
function voteAt(float $start, int $userId, int $featureRequestId): Closure
{
    return function () use ($start, $userId, $featureRequestId) {
        time_sleep_until($start);

        try {
            app(CastVote::class)->handle(User::query()->findOrFail($userId), FeatureRequest::query()->findOrFail($featureRequestId));

            return 'accepted';
        } catch (AuthorizationException) {
            return 'rejected';
        }
    };
}

/**
 * Build a task that declines at a shared start time and commits only after a second.
 */
function slowDeclineAt(float $start, int $adminId, int $featureRequestId): Closure
{
    return function () use ($start, $adminId, $featureRequestId) {
        time_sleep_until($start);

        DB::transaction(function () use ($adminId, $featureRequestId) {
            app(ChangeStatus::class)->handle(User::query()->findOrFail($adminId), FeatureRequest::query()->findOrFail($featureRequestId), FeatureRequestStatus::Declined);

            usleep(1_000_000);
        });
    };
}

test('two users voting at the same time are both counted', function () {
    $featureRequest = FeatureRequest::factory()->create();
    $start = microtime(true) + 2;

    Concurrency::run([
        voteAt($start, User::factory()->create()->id, $featureRequest->id),
        voteAt($start, User::factory()->create()->id, $featureRequest->id),
    ]);

    expect($featureRequest->fresh()?->votes_count)->toBe(2)
        ->and($featureRequest->votes()->count())->toBe(2);
});

test('the same user voting twice at the same time is counted once', function () {
    $user = User::factory()->create();
    $featureRequest = FeatureRequest::factory()->create();
    $start = microtime(true) + 2;

    Concurrency::run([
        voteAt($start, $user->id, $featureRequest->id),
        voteAt($start, $user->id, $featureRequest->id),
    ]);

    expect($featureRequest->fresh()?->votes_count)->toBe(1)
        ->and($featureRequest->votes()->count())->toBe(1);
});

test('a vote arriving during a decline is rejected once the decline commits', function () {
    $featureRequest = FeatureRequest::factory()->create();
    $start = microtime(true) + 2;

    [, $outcome] = Concurrency::run([
        slowDeclineAt($start, User::factory()->admin()->create()->id, $featureRequest->id),
        voteAt($start + 0.5, User::factory()->create()->id, $featureRequest->id),
    ]);

    expect($outcome)->toBe('rejected')
        ->and($featureRequest->fresh()?->votes_count)->toBe(0)
        ->and($featureRequest->votes()->count())->toBe(0);
});
