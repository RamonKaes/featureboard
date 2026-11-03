<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\FeatureRequestSort;
use App\Enums\FeatureRequestStatus;
use App\Http\Resources\FeatureRequestSummaryResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class RoadmapController extends Controller
{
    /**
     * Show the public roadmap of a product, with one column per status.
     */
    public function __invoke(Product $product): Response
    {
        $featureRequests = $product->featureRequests()
            ->whereIn('status', FeatureRequestStatus::onRoadmap())
            ->sortedBy(FeatureRequestSort::Votes)
            ->get()
            ->groupBy(fn ($featureRequest) => $featureRequest->status->value);

        return Inertia::render('roadmap/Show', [
            'product' => new ProductResource($product),
            'columns' => array_map(fn (FeatureRequestStatus $status) => [
                'status' => ['value' => $status->value, 'label' => $status->label()],
                'featureRequests' => FeatureRequestSummaryResource::collection($featureRequests->get($status->value, collect())),
            ], FeatureRequestStatus::onRoadmap()),
        ]);
    }
}
