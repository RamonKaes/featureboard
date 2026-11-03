<?php

declare(strict_types=1);

use App\Models\FeatureRequest;
use Illuminate\Database\LazyLoadingViolationException;

test('lazy loading on a list of models throws outside production', function () {
    FeatureRequest::factory()->count(2)->create();

    $featureRequests = FeatureRequest::query()->get();

    expect(fn () => $featureRequests->first()?->author)
        ->toThrow(LazyLoadingViolationException::class);
});
