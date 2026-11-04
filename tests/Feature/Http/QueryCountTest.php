<?php

declare(strict_types=1);

use App\Models\FeatureRequest;
use App\Models\Product;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Support\Facades\DB;

function countQueries(Closure $request): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    $request();
    DB::disableQueryLog();

    return count(DB::getQueryLog());
}

test('the request list needs the same number of queries for one or many rows', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    FeatureRequest::factory()->for($product)->create();

    $this->actingAs($user);
    $one = countQueries(fn () => $this->get(route('products.show', $product))->assertOk());

    FeatureRequest::factory()->count(4)->for($product)->create()
        ->each(fn (FeatureRequest $featureRequest) => Vote::factory()->for($user)->for($featureRequest)->create());
    $many = countQueries(fn () => $this->get(route('products.show', $product))->assertOk());

    expect($many)->toBe($one);
});

test('the product list needs the same number of queries for one or many rows', function () {
    Product::factory()->create();
    $one = countQueries(fn () => $this->get(route('home'))->assertOk());

    Product::factory()->count(4)->create()
        ->each(fn (Product $product) => FeatureRequest::factory()->for($product)->create());
    $many = countQueries(fn () => $this->get(route('home'))->assertOk());

    expect($many)->toBe($one);
});

test('the detail page needs the same number of queries for few or many votes', function () {
    $user = User::factory()->create();
    $featureRequest = FeatureRequest::factory()->create();
    Vote::factory()->for($featureRequest)->create();
    $url = route('feature-requests.show', [$featureRequest->product, $featureRequest]);

    $this->actingAs($user);
    $few = countQueries(fn () => $this->get($url)->assertOk());

    Vote::factory()->count(5)->for($featureRequest)->create();
    $many = countQueries(fn () => $this->get($url)->assertOk());

    expect($many)->toBe($few);
});

test('the search for similar requests needs the same number of queries for one or many rows', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    FeatureRequest::factory()->for($product)->create(['title' => 'Mahnung eins']);
    $url = route('feature-requests.similar', [$product, 'title' => 'Mahnung']);

    $this->actingAs($user);
    $one = countQueries(fn () => $this->getJson($url)->assertOk());

    FeatureRequest::factory()->count(4)->for($product)->create(['title' => 'Mahnung viele'])
        ->each(fn (FeatureRequest $featureRequest) => Vote::factory()->for($user)->for($featureRequest)->create());

    $five = countQueries(fn () => $this->getJson($url)->assertOk()->assertJsonCount(5));

    expect($five)->toBe($one);
});
