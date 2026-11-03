<?php

declare(strict_types=1);

use App\Models\FeatureRequest;
use App\Models\Product;
use Inertia\Testing\AssertableInertia as Assert;

test('guests see all products with their request counts, sorted by name', function () {
    $zebra = Product::factory()->create(['name' => 'Zebra']);
    Product::factory()->create(['name' => 'Ameise', 'slug' => 'ameise']);
    FeatureRequest::factory()->count(2)->for($zebra)->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('products/Index')
            ->has('products', 2)
            ->where('products.0.name', 'Ameise')
            ->where('products.0.slug', 'ameise')
            ->where('products.0.featureRequestsCount', 0)
            ->where('products.1.featureRequestsCount', 2));
});
