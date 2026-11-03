<?php

declare(strict_types=1);

use App\Enums\FeatureRequestStatus as Status;
use App\Models\FeatureRequest;
use App\Models\Product;
use App\Models\User;
use App\Models\Vote;
use Inertia\Testing\AssertableInertia as Assert;

test('guests see the feature requests of a product', function () {
    $product = Product::factory()->create();
    $featureRequest = FeatureRequest::factory()->for($product)->create();
    FeatureRequest::factory()->create();

    $this->get(route('products.show', $product))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('featureRequests/Index')
            ->where('product.slug', $product->slug)
            ->has('featureRequests.data', 1)
            ->where('featureRequests.data.0.id', $featureRequest->id)
            ->where('featureRequests.data.0.status.value', 'new')
            ->where('featureRequests.data.0.status.label', 'Neu')
            ->where('featureRequests.data.0.authorName', $featureRequest->author?->name)
            ->where('featureRequests.data.0.hasVoted', false)
            ->where('featureRequests.data.0.can.vote', false));
});

test('an unknown product responds with 404', function () {
    $this->get('/products/unknown')->assertNotFound();
});

test('requests are sorted by votes by default', function () {
    $product = Product::factory()->create();
    $few = FeatureRequest::factory()->for($product)->create(['votes_count' => 1]);
    $many = FeatureRequest::factory()->for($product)->create(['votes_count' => 9]);

    $this->get(route('products.show', $product))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.sort', 'votes')
            ->where('featureRequests.data.0.id', $many->id)
            ->where('featureRequests.data.1.id', $few->id));
});

test('requests can be sorted by date', function () {
    $product = Product::factory()->create();
    $old = FeatureRequest::factory()->for($product)->create(['votes_count' => 9, 'created_at' => now()->subWeek()]);
    $new = FeatureRequest::factory()->for($product)->create(['votes_count' => 1]);

    $this->get(route('products.show', [$product, 'sort' => 'newest']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.sort', 'newest')
            ->where('featureRequests.data.0.id', $new->id)
            ->where('featureRequests.data.1.id', $old->id));
});

test('requests can be filtered by status', function () {
    $product = Product::factory()->create();
    $planned = FeatureRequest::factory()->for($product)->create(['status' => Status::Planned]);
    FeatureRequest::factory()->for($product)->create(['status' => Status::New]);

    $this->get(route('products.show', [$product, 'status' => 'planned']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.status', 'planned')
            ->has('featureRequests.data', 1)
            ->where('featureRequests.data.0.id', $planned->id));
});

test('unknown filter values fall back to the defaults', function () {
    $product = Product::factory()->create();
    FeatureRequest::factory()->for($product)->create();

    $this->get(route('products.show', [$product, 'status' => 'nonsense', 'sort' => 'nonsense']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.status', null)
            ->where('filters.sort', 'votes')
            ->has('featureRequests.data', 1));
});

test('the list offers the filter options with German labels', function () {
    $this->get(route('products.show', Product::factory()->create()))
        ->assertInertia(fn (Assert $page) => $page
            ->has('statusOptions', 5)
            ->where('statusOptions.2', ['value' => 'in_progress', 'label' => 'In Arbeit'])
            ->where('sortOptions.1', ['value' => 'newest', 'label' => 'Neueste zuerst']));
});

test('the list is paginated and keeps the filters in its links', function () {
    $product = Product::factory()->create();
    FeatureRequest::factory()->count(7)->for($product)->create(['status' => Status::Planned]);

    $this->get(route('products.show', [$product, 'status' => 'planned', 'page' => 2]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('featureRequests.data', 2)
            ->where('featureRequests.meta.current_page', 2)
            ->where('featureRequests.meta.last_page', 2)
            ->where('featureRequests.links.prev', fn (string $link) => str_contains($link, 'status=planned')));
});

test('logged in users see their own vote and permissions', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    $voted = FeatureRequest::factory()->for($product)->create(['votes_count' => 1]);
    Vote::factory()->for($user)->for($voted)->create();
    $own = FeatureRequest::factory()->for($product)->for($user, 'author')->create();

    $this->actingAs($user)
        ->get(route('products.show', $product))
        ->assertInertia(fn (Assert $page) => $page
            ->where('featureRequests.data.0.id', $voted->id)
            ->where('featureRequests.data.0.hasVoted', true)
            ->where('featureRequests.data.0.can.vote', true)
            ->where('featureRequests.data.0.can.update', false)
            ->where('featureRequests.data.1.id', $own->id)
            ->where('featureRequests.data.1.hasVoted', false)
            ->where('featureRequests.data.1.can.vote', false)
            ->where('featureRequests.data.1.can.update', true));
});

test('a request without author stays visible', function () {
    $product = Product::factory()->create();
    FeatureRequest::factory()->for($product)->create(['user_id' => null]);

    $this->get(route('products.show', $product))
        ->assertInertia(fn (Assert $page) => $page->where('featureRequests.data.0.authorName', null));
});
