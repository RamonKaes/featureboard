<?php

declare(strict_types=1);

use App\Enums\FeatureRequestStatus;
use App\Models\FeatureRequest;
use App\Models\Product;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->seed();
});

test('it seeds two to three products with feature requests', function () {
    expect(Product::query()->count())->toBeBetween(2, 3)
        ->and(Product::query()->doesntHave('featureRequests')->count())->toBe(0);
});

test('it seeds feature requests in every status', function () {
    $statuses = FeatureRequest::query()->pluck('status')->unique();

    expect($statuses)->toHaveCount(count(FeatureRequestStatus::cases()));
});

test('the documented accounts can log in', function (string $email, bool $isAdmin) {
    expect(Auth::attempt(['email' => $email, 'password' => 'password']))->toBeTrue()
        ->and(Auth::user())->toBeInstanceOf(User::class)
        ->and(User::query()->where('email', $email)->sole()->is_admin)->toBe($isAdmin);
})->with([
    'admin' => ['admin@featureboard.test', true],
    'demo' => ['demo@featureboard.test', false],
]);

test('only one account is an admin', function () {
    expect(User::query()->where('is_admin', true)->count())->toBe(1);
});

test('the demo user owns an editable feature request', function () {
    $demo = User::query()->where('email', 'demo@featureboard.test')->sole();

    expect($demo->featureRequests()->where('status', FeatureRequestStatus::New)->exists())->toBeTrue();
});

test('the demo user can still vote for open feature requests', function () {
    $demo = User::query()->where('email', 'demo@featureboard.test')->sole();

    $open = FeatureRequest::query()
        ->whereIn('status', [FeatureRequestStatus::New, FeatureRequestStatus::Planned, FeatureRequestStatus::InProgress])
        ->whereNot('user_id', $demo->id)
        ->whereDoesntHave('votes', fn ($votes) => $votes->where('user_id', $demo->id));

    expect($open->exists())->toBeTrue();
});

test('the vote counts match the stored votes', function () {
    FeatureRequest::query()->withCount('votes as stored_votes')->get()
        ->each(fn (FeatureRequest $featureRequest) => expect($featureRequest->votes_count)
            ->toBe($featureRequest->getAttribute('stored_votes')));
});

test('nobody has voted for their own feature request', function () {
    $selfVotes = Vote::query()
        ->whereHas('featureRequest', fn ($featureRequest) => $featureRequest->whereColumn('feature_requests.user_id', 'votes.user_id'))
        ->count();

    expect($selfVotes)->toBe(0);
});
