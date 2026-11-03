<?php

declare(strict_types=1);

use App\Actions\ChangeStatus;
use App\Enums\FeatureRequestStatus as Status;
use App\Exceptions\InvalidStatusTransition;
use App\Models\FeatureRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

test('admins can make an allowed transition', function (Status $from, Status $to) {
    $featureRequest = FeatureRequest::factory()->create(['status' => $from]);

    $updated = app(ChangeStatus::class)->handle(User::factory()->admin()->create(), $featureRequest, $to);

    expect($updated->status)->toBe($to)
        ->and($featureRequest->fresh()?->status)->toBe($to);
})->with([
    'new to planned' => [Status::New, Status::Planned],
    'new to declined' => [Status::New, Status::Declined],
    'planned to in progress' => [Status::Planned, Status::InProgress],
    'planned to declined' => [Status::Planned, Status::Declined],
    'in progress to done' => [Status::InProgress, Status::Done],
]);

test('forbidden transitions are rejected and change nothing', function (Status $from, Status $to) {
    $featureRequest = FeatureRequest::factory()->create(['status' => $from]);

    expect(fn () => app(ChangeStatus::class)->handle(User::factory()->admin()->create(), $featureRequest, $to))
        ->toThrow(InvalidStatusTransition::class);

    expect($featureRequest->fresh()?->status)->toBe($from);
})->with([
    'new to done' => [Status::New, Status::Done],
    'in progress to declined' => [Status::InProgress, Status::Declined],
    'done to new' => [Status::Done, Status::New],
    'declined to planned' => [Status::Declined, Status::Planned],
    'planned to planned' => [Status::Planned, Status::Planned],
]);

test('normal users cannot change the status', function () {
    $featureRequest = FeatureRequest::factory()->create();

    expect(fn () => app(ChangeStatus::class)->handle(User::factory()->create(), $featureRequest, Status::Planned))
        ->toThrow(AuthorizationException::class);

    expect($featureRequest->fresh()?->status)->toBe(Status::New);
});

test('the transition is checked against the current status, not a stale model', function () {
    $stale = FeatureRequest::factory()->create();
    FeatureRequest::query()->whereKey($stale->id)->update(['status' => Status::Declined]);

    expect(fn () => app(ChangeStatus::class)->handle(User::factory()->admin()->create(), $stale, Status::Planned))
        ->toThrow(InvalidStatusTransition::class);
});
