<?php

declare(strict_types=1);

use App\Enums\FeatureRequestStatus as Status;
use App\Models\FeatureRequest;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

test('guests can see the roadmap, grouped in workflow order', function () {
    $product = Product::factory()->create();
    $done = FeatureRequest::factory()->for($product)->create(['status' => Status::Done]);
    $inProgress = FeatureRequest::factory()->for($product)->create(['status' => Status::InProgress]);
    $planned = FeatureRequest::factory()->for($product)->create(['status' => Status::Planned]);

    $this->get(route('products.roadmap', $product))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('roadmap/Show')
            ->where('product.slug', $product->slug)
            ->has('columns', 3)
            ->where('columns.0.status', ['value' => 'planned', 'label' => 'Geplant'])
            ->where('columns.0.featureRequests.0.id', $planned->id)
            ->where('columns.1.status', ['value' => 'in_progress', 'label' => 'In Arbeit'])
            ->where('columns.1.featureRequests.0.id', $inProgress->id)
            ->where('columns.2.status', ['value' => 'done', 'label' => 'Erledigt'])
            ->where('columns.2.featureRequests.0.id', $done->id));
});

test('new and declined requests stay off the roadmap', function () {
    $product = Product::factory()->create();
    FeatureRequest::factory()->for($product)->create(['status' => Status::New]);
    FeatureRequest::factory()->for($product)->create(['status' => Status::Declined]);

    $this->get(route('products.roadmap', $product))
        ->assertInertia(fn (Assert $page) => $page
            ->has('columns.0.featureRequests', 0)
            ->has('columns.1.featureRequests', 0)
            ->has('columns.2.featureRequests', 0));
});

test('the roadmap shows only the requests of its product', function () {
    FeatureRequest::factory()->create(['status' => Status::Planned]);

    $this->get(route('products.roadmap', Product::factory()->create()))
        ->assertInertia(fn (Assert $page) => $page->has('columns.0.featureRequests', 0));
});

test('each column is sorted by votes', function () {
    $product = Product::factory()->create();
    $few = FeatureRequest::factory()->for($product)->create(['status' => Status::Planned, 'votes_count' => 1]);
    $many = FeatureRequest::factory()->for($product)->create(['status' => Status::Planned, 'votes_count' => 9]);

    $this->get(route('products.roadmap', $product))
        ->assertInertia(fn (Assert $page) => $page
            ->where('columns.0.featureRequests.0.id', $many->id)
            ->where('columns.0.featureRequests.1.id', $few->id));
});

test('an unknown product responds with 404', function () {
    $this->get('/products/unknown/roadmap')->assertNotFound();
});

test('the roadmap needs the same number of queries for one or many rows', function () {
    $product = Product::factory()->create();
    FeatureRequest::factory()->for($product)->create(['status' => Status::Planned]);

    DB::enableQueryLog();
    $this->get(route('products.roadmap', $product))->assertOk();
    $one = count(DB::getQueryLog());

    FeatureRequest::factory()->count(5)->for($product)->create(['status' => Status::Done]);

    DB::flushQueryLog();
    $this->get(route('products.roadmap', $product))->assertOk();

    expect(count(DB::getQueryLog()))->toBe($one);
});
