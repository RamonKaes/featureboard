<?php

declare(strict_types=1);

use App\Models\FeatureRequest;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;

test('a product has many feature requests', function () {
    $product = Product::factory()->create();
    $featureRequest = FeatureRequest::factory()->for($product)->create();
    FeatureRequest::factory()->create();

    expect($product->featureRequests)->toHaveCount(1)
        ->and($product->featureRequests->sole()->is($featureRequest))->toBeTrue();
});

test('the slug must be unique', function () {
    Product::factory()->create(['slug' => 'taken']);

    expect(fn () => Product::factory()->create(['slug' => 'taken']))
        ->toThrow(UniqueConstraintViolationException::class);
});

test('a product with feature requests cannot be deleted', function () {
    $product = FeatureRequest::factory()->create()->product;

    expect(fn () => $product->delete())
        ->toThrow(QueryException::class, 'feature_requests_product_id_foreign');
});

test('a product without feature requests can be deleted', function () {
    $product = Product::factory()->create();

    $product->delete();

    expect(Product::query()->count())->toBe(0);
});
