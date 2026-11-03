<?php

declare(strict_types=1);

use App\Enums\FeatureRequestStatus as Status;
use App\Models\FeatureRequest;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

$valid = ['title' => 'Neuer Titel', 'description' => 'Eine neue Beschreibung.'];

test('authors see the edit form', function () {
    $featureRequest = FeatureRequest::factory()->create();

    $this->actingAs($featureRequest->author)
        ->get(route('feature-requests.edit', [$featureRequest->product, $featureRequest]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('featureRequests/Edit')
            ->where('featureRequest.title', $featureRequest->title));
});

test('authors can update while the status is new', function () use ($valid) {
    $featureRequest = FeatureRequest::factory()->create();

    $this->actingAs($featureRequest->author)
        ->put(route('feature-requests.update', [$featureRequest->product, $featureRequest]), $valid)
        ->assertRedirect(route('feature-requests.show', [$featureRequest->product, $featureRequest]));

    expect($featureRequest->fresh()?->title)->toBe('Neuer Titel');
});

test('authors cannot edit after the status has changed', function (Status $status) use ($valid) {
    $featureRequest = FeatureRequest::factory()->create(['status' => $status]);
    $route = [$featureRequest->product, $featureRequest];

    $this->actingAs($featureRequest->author)->get(route('feature-requests.edit', $route))->assertForbidden();
    $this->actingAs($featureRequest->author)->put(route('feature-requests.update', $route), $valid)->assertForbidden();

    expect($featureRequest->fresh()?->title)->toBe($featureRequest->title);
})->with([Status::Planned, Status::InProgress, Status::Done, Status::Declined]);

test('other users and admins cannot edit', function (bool $admin) use ($valid) {
    $featureRequest = FeatureRequest::factory()->create();
    $user = User::factory()->create(['is_admin' => $admin]);
    $route = [$featureRequest->product, $featureRequest];

    $this->actingAs($user)->get(route('feature-requests.edit', $route))->assertForbidden();
    $this->actingAs($user)->put(route('feature-requests.update', $route), $valid)->assertForbidden();

    expect($featureRequest->fresh()?->title)->toBe($featureRequest->title);
})->with(['other user' => false, 'admin' => true]);

test('guests are sent to the login page', function () use ($valid) {
    $featureRequest = FeatureRequest::factory()->create();
    $route = [$featureRequest->product, $featureRequest];

    $this->get(route('feature-requests.edit', $route))->assertRedirect(route('login'));
    $this->put(route('feature-requests.update', $route), $valid)->assertRedirect(route('login'));
});

test('a request cannot be edited through another product', function () use ($valid) {
    $featureRequest = FeatureRequest::factory()->create();
    $other = FeatureRequest::factory()->create();

    $this->actingAs($featureRequest->author)
        ->put(route('feature-requests.update', [$other->product, $featureRequest]), $valid)
        ->assertNotFound();
});

test('updates are validated in German', function () {
    $featureRequest = FeatureRequest::factory()->create();

    $this->actingAs($featureRequest->author)
        ->put(route('feature-requests.update', [$featureRequest->product, $featureRequest]), ['title' => '', 'description' => ''])
        ->assertSessionHasErrors(['title' => 'Bitte gib Titel an.', 'description' => 'Bitte gib Beschreibung an.']);
});

test('the status cannot be changed through the edit form', function () use ($valid) {
    $featureRequest = FeatureRequest::factory()->create();

    $this->actingAs($featureRequest->author)
        ->put(route('feature-requests.update', [$featureRequest->product, $featureRequest]), [...$valid, 'status' => 'done']);

    expect($featureRequest->fresh()?->status)->toBe(Status::New);
});
