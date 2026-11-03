<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SimilarFeatureRequestsRequest;
use App\Http\Resources\SimilarFeatureRequestResource;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SimilarFeatureRequestController extends Controller
{
    private const int LIMIT = 5;

    /**
     * Find existing feature requests of the product that resemble the typed title.
     */
    public function __invoke(SimilarFeatureRequestsRequest $request, Product $product): AnonymousResourceCollection
    {
        $featureRequests = $product->featureRequests()
            ->similarTo($request->string('title')->toString())
            ->when($request->integer('except'), fn ($query, int $except) => $query->whereKeyNot($except))
            ->limit(self::LIMIT)
            ->get();

        return SimilarFeatureRequestResource::collection($featureRequests);
    }
}
