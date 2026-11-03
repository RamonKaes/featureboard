<?php

declare(strict_types=1);

use App\Enums\FeatureRequestStatus as Status;
use App\Models\FeatureRequest;
use App\Models\User;
use App\Models\Vote;

test('logged in users can vote and return to the previous page', function () {
    $featureRequest = FeatureRequest::factory()->create();

    $this->actingAs(User::factory()->create())
        ->from('/somewhere')
        ->post(route('votes.store', $featureRequest))
        ->assertRedirect('/somewhere');

    expect($featureRequest->fresh()?->votes_count)->toBe(1);
});

test('logged in users can retract a vote', function () {
    $vote = Vote::factory()->create();
    $featureRequest = $vote->featureRequest;
    $featureRequest->forceFill(['votes_count' => 1])->save();

    $this->actingAs($vote->user)
        ->from('/somewhere')
        ->delete(route('votes.destroy', $featureRequest))
        ->assertRedirect('/somewhere');

    expect($featureRequest->fresh()?->votes_count)->toBe(0);
});

test('guests are sent to the login page', function () {
    $featureRequest = FeatureRequest::factory()->create();

    $this->post(route('votes.store', $featureRequest))->assertRedirect(route('login'));
    $this->delete(route('votes.destroy', $featureRequest))->assertRedirect(route('login'));

    expect($featureRequest->votes()->count())->toBe(0);
});

test('authors cannot vote for their own feature request', function () {
    $featureRequest = FeatureRequest::factory()->create();

    $this->actingAs($featureRequest->author)
        ->post(route('votes.store', $featureRequest))
        ->assertForbidden();

    expect($featureRequest->votes()->count())->toBe(0);
});

test('finished feature requests cannot receive votes', function (Status $status) {
    $featureRequest = FeatureRequest::factory()->create(['status' => $status]);

    $this->actingAs(User::factory()->create())
        ->post(route('votes.store', $featureRequest))
        ->assertForbidden();

    expect($featureRequest->votes()->count())->toBe(0);
})->with([Status::Done, Status::Declined]);

test('a rejected vote from the interface returns to the page with an error toast', function () {
    $featureRequest = FeatureRequest::factory()->create();

    $this->actingAs($featureRequest->author)
        ->from('/somewhere')
        ->withHeaders(['X-Inertia' => 'true', 'X-Requested-With' => 'XMLHttpRequest'])
        ->post(route('votes.store', $featureRequest))
        ->assertRedirect('/somewhere')
        ->assertInertiaFlash('toast', [
            'type' => 'error',
            'message' => 'Diese Aktion ist nicht erlaubt.',
        ]);
});
