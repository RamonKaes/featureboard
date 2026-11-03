<?php

declare(strict_types=1);

use App\Enums\FeatureRequestStatus as Status;
use App\Models\FeatureRequest;
use App\Models\User;

describe('vote', function () {
    test('other users may vote', function () {
        $featureRequest = FeatureRequest::factory()->create();

        expect(User::factory()->create()->can('vote', $featureRequest))->toBeTrue();
    });

    test('authors may not vote for their own feature request', function () {
        $featureRequest = FeatureRequest::factory()->create();

        expect($featureRequest->author?->can('vote', $featureRequest))->toBeFalse();
    });

    test('admins may not vote for their own feature request either', function () {
        $admin = User::factory()->admin()->create();
        $featureRequest = FeatureRequest::factory()->for($admin, 'author')->create();

        expect($admin->can('vote', $featureRequest))->toBeFalse();
    });

    test('votes depend on the status', function (Status $status, bool $allowed) {
        $featureRequest = FeatureRequest::factory()->create(['status' => $status]);

        expect(User::factory()->create()->can('vote', $featureRequest))->toBe($allowed);
    })->with([
        'new' => [Status::New, true],
        'planned' => [Status::Planned, true],
        'in progress' => [Status::InProgress, true],
        'done' => [Status::Done, false],
        'declined' => [Status::Declined, false],
    ]);
});

describe('update', function () {
    test('authors may edit only while the status is new', function (Status $status, bool $allowed) {
        $featureRequest = FeatureRequest::factory()->create(['status' => $status]);

        expect($featureRequest->author?->can('update', $featureRequest))->toBe($allowed);
    })->with([
        'new' => [Status::New, true],
        'planned' => [Status::Planned, false],
        'in progress' => [Status::InProgress, false],
        'done' => [Status::Done, false],
        'declined' => [Status::Declined, false],
    ]);

    test('other users may not edit', function () {
        $featureRequest = FeatureRequest::factory()->create();

        expect(User::factory()->create()->can('update', $featureRequest))->toBeFalse();
    });

    test('admins may not edit the requests of others', function () {
        $featureRequest = FeatureRequest::factory()->create();

        expect(User::factory()->admin()->create()->can('update', $featureRequest))->toBeFalse();
    });
});

describe('changeStatus', function () {
    test('admins may change the status', function () {
        $featureRequest = FeatureRequest::factory()->create();

        expect(User::factory()->admin()->create()->can('changeStatus', $featureRequest))->toBeTrue();
    });

    test('normal users may not change the status', function () {
        $featureRequest = FeatureRequest::factory()->create();

        expect(User::factory()->create()->can('changeStatus', $featureRequest))->toBeFalse();
    });

    test('authors may not change the status of their own feature request', function () {
        $featureRequest = FeatureRequest::factory()->create();

        expect($featureRequest->author?->can('changeStatus', $featureRequest))->toBeFalse();
    });
});
