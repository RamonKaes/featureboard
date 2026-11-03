<?php

declare(strict_types=1);

use App\Enums\FeatureRequestStatus as Status;

test('only the defined status transitions are allowed', function (Status $from, Status $to, bool $allowed) {
    expect($from->canTransitionTo($to))->toBe($allowed);
})->with(function () {
    $allowed = [
        'new' => ['planned', 'declined'],
        'planned' => ['in_progress', 'declined'],
        'in_progress' => ['done'],
        'done' => [],
        'declined' => [],
    ];

    foreach (Status::cases() as $from) {
        foreach (Status::cases() as $to) {
            yield "{$from->value} to {$to->value}" => [$from, $to, in_array($to->value, $allowed[$from->value], true)];
        }
    }
});

test('finished feature requests no longer accept votes', function (Status $status, bool $acceptsVotes) {
    expect($status->acceptsVotes())->toBe($acceptsVotes);
})->with([
    'new' => [Status::New, true],
    'planned' => [Status::Planned, true],
    'in progress' => [Status::InProgress, true],
    'done' => [Status::Done, false],
    'declined' => [Status::Declined, false],
]);

test('only new feature requests are editable', function (Status $status, bool $editable) {
    expect($status->isEditable())->toBe($editable);
})->with([
    'new' => [Status::New, true],
    'planned' => [Status::Planned, false],
    'in progress' => [Status::InProgress, false],
    'done' => [Status::Done, false],
    'declined' => [Status::Declined, false],
]);
