<?php

declare(strict_types=1);

use App\Enums\FeatureRequestStatus as Status;
use App\Models\FeatureRequest;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('admins see only the allowed transitions on the detail page', function (Status $status, array $expected) {
    $featureRequest = FeatureRequest::factory()->create(['status' => $status]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('feature-requests.show', [$featureRequest->product, $featureRequest]))
        ->assertInertia(fn (Assert $page) => $page->where('allowedStatuses', $expected));
})->with([
    'new' => [Status::New, [['value' => 'planned', 'label' => 'Geplant'], ['value' => 'declined', 'label' => 'Abgelehnt']]],
    'planned' => [Status::Planned, [['value' => 'in_progress', 'label' => 'In Arbeit'], ['value' => 'declined', 'label' => 'Abgelehnt']]],
    'in progress' => [Status::InProgress, [['value' => 'done', 'label' => 'Erledigt']]],
    'done' => [Status::Done, []],
    'declined' => [Status::Declined, []],
]);

test('users and guests are offered no status changes', function () {
    $featureRequest = FeatureRequest::factory()->create();
    $route = route('feature-requests.show', [$featureRequest->product, $featureRequest]);

    $this->get($route)->assertInertia(fn (Assert $page) => $page->where('allowedStatuses', []));
    $this->actingAs($featureRequest->author)->get($route)->assertInertia(fn (Assert $page) => $page->where('allowedStatuses', []));
});

test('admins can make an allowed transition', function () {
    $featureRequest = FeatureRequest::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->from('/somewhere')
        ->put(route('feature-requests.status.update', $featureRequest), ['status' => 'planned'])
        ->assertRedirect('/somewhere')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Status geändert: Geplant']);

    expect($featureRequest->fresh()?->status)->toBe(Status::Planned);
});

test('the backend rejects a forbidden transition even when it is sent directly', function (Status $from, string $to) {
    $featureRequest = FeatureRequest::factory()->create(['status' => $from]);

    $this->actingAs(User::factory()->admin()->create())
        ->from('/somewhere')
        ->put(route('feature-requests.status.update', $featureRequest), ['status' => $to])
        ->assertRedirect('/somewhere')
        ->assertInertiaFlash('toast', ['type' => 'error', 'message' => 'Dieser Statuswechsel ist nicht erlaubt.']);

    expect($featureRequest->fresh()?->status)->toBe($from);
})->with([
    'new to done' => [Status::New, 'done'],
    'done to new' => [Status::Done, 'new'],
    'declined to planned' => [Status::Declined, 'planned'],
    'in progress to declined' => [Status::InProgress, 'declined'],
]);

test('normal users and authors cannot change the status', function () {
    $featureRequest = FeatureRequest::factory()->create();
    $route = route('feature-requests.status.update', $featureRequest);

    $this->actingAs(User::factory()->create())->put($route, ['status' => 'planned'])->assertForbidden();
    $this->actingAs($featureRequest->author)->put($route, ['status' => 'planned'])->assertForbidden();

    expect($featureRequest->fresh()?->status)->toBe(Status::New);
});

test('guests are sent to the login page', function () {
    $featureRequest = FeatureRequest::factory()->create();

    $this->put(route('feature-requests.status.update', $featureRequest), ['status' => 'planned'])
        ->assertRedirect(route('login'));

    expect($featureRequest->fresh()?->status)->toBe(Status::New);
});

test('unknown statuses are rejected in German', function (string $status, string $message) {
    $featureRequest = FeatureRequest::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('feature-requests.status.update', $featureRequest), ['status' => $status])
        ->assertSessionHasErrors(['status' => $message]);
})->with([
    'unknown' => ['archived', 'Status ist ungültig.'],
    'missing' => ['', 'Bitte gib Status an.'],
]);
