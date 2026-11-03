<?php

declare(strict_types=1);

use App\Models\FeatureRequest;
use App\Models\User;
use App\Models\Vote;
use Inertia\Testing\AssertableInertia as Assert;

test('guests see a feature request', function () {
    $featureRequest = FeatureRequest::factory()->create();

    $this->get(route('feature-requests.show', [$featureRequest->product, $featureRequest]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('featureRequests/Show')
            ->where('product.slug', $featureRequest->product->slug)
            ->where('featureRequest.title', $featureRequest->title)
            ->where('featureRequest.description', $featureRequest->description)
            ->where('featureRequest.hasVoted', false));
});

test('logged in users see their vote', function () {
    $vote = Vote::factory()->create();
    $featureRequest = $vote->featureRequest;

    $this->actingAs($vote->user)
        ->get(route('feature-requests.show', [$featureRequest->product, $featureRequest]))
        ->assertInertia(fn (Assert $page) => $page->where('featureRequest.hasVoted', true));
});

test('a request cannot be opened through another product', function () {
    $featureRequest = FeatureRequest::factory()->create();
    $other = FeatureRequest::factory()->create();

    $this->get(route('feature-requests.show', [$other->product, $featureRequest]))
        ->assertNotFound();
});

test('user text is passed on unchanged for the frontend to escape', function () {
    $featureRequest = FeatureRequest::factory()->create(['title' => '<script>alert(1)</script>']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('feature-requests.show', [$featureRequest->product, $featureRequest]))
        ->assertInertia(fn (Assert $page) => $page->where('featureRequest.title', '<script>alert(1)</script>'))
        ->assertDontSee('<script>alert(1)</script>', false);
});
