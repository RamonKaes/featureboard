<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\FeatureRequestSort;
use App\Enums\FeatureRequestStatus;
use App\Http\Requests\SaveFeatureRequestRequest;
use App\Http\Resources\FeatureRequestResource;
use App\Http\Resources\ProductResource;
use App\Models\FeatureRequest;
use App\Models\Product;
use App\Models\User;
use BackedEnum;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeatureRequestController extends Controller
{
    private const int PER_PAGE = 5;

    /**
     * Show the filtered and sorted feature requests of a product.
     */
    public function index(Request $request, Product $product): Response
    {
        $status = $request->enum('status', FeatureRequestStatus::class);
        $sort = $request->enum('sort', FeatureRequestSort::class) ?? FeatureRequestSort::Votes;

        $featureRequests = $product->featureRequests()
            ->with('author:id,name')
            ->withVoteOf($request->user())
            ->when($status, fn ($query) => $query->where('status', $status))
            ->sortedBy($sort)
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('featureRequests/Index', [
            'product' => new ProductResource($product),
            'featureRequests' => FeatureRequestResource::collection($featureRequests),
            'filters' => ['status' => $status?->value, 'sort' => $sort->value],
            'statusOptions' => $this->options(FeatureRequestStatus::cases()),
            'sortOptions' => $this->options(FeatureRequestSort::cases()),
        ]);
    }

    /**
     * Show the form for a new feature request.
     */
    public function create(Product $product): Response
    {
        return Inertia::render('featureRequests/Create', [
            'product' => new ProductResource($product),
        ]);
    }

    /**
     * Store a new feature request.
     */
    public function store(SaveFeatureRequestRequest $request, Product $product, #[CurrentUser] User $user): RedirectResponse
    {
        $featureRequest = $product->featureRequests()->make($request->validated());
        $featureRequest->author()->associate($user)->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Dein Wunsch wurde eingereicht.']);

        return to_route('feature-requests.show', [$product, $featureRequest]);
    }

    /**
     * Show a single feature request.
     */
    public function show(Request $request, Product $product, FeatureRequest $featureRequest): Response
    {
        return Inertia::render('featureRequests/Show', [
            'product' => new ProductResource($product),
            'featureRequest' => new FeatureRequestResource($this->withViewerData($featureRequest, $request->user())),
            'allowedStatuses' => $request->user()?->can('changeStatus', $featureRequest)
                ? $this->options($featureRequest->status->allowedTransitions())
                : [],
        ]);
    }

    /**
     * Show the form for editing a feature request.
     */
    public function edit(Request $request, Product $product, FeatureRequest $featureRequest): Response
    {
        return Inertia::render('featureRequests/Edit', [
            'product' => new ProductResource($product),
            'featureRequest' => new FeatureRequestResource($this->withViewerData($featureRequest, $request->user())),
        ]);
    }

    /**
     * Update a feature request.
     */
    public function update(SaveFeatureRequestRequest $request, Product $product, FeatureRequest $featureRequest): RedirectResponse
    {
        $featureRequest->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Dein Wunsch wurde gespeichert.']);

        return to_route('feature-requests.show', [$product, $featureRequest]);
    }

    private function withViewerData(FeatureRequest $featureRequest, ?User $viewer): FeatureRequest
    {
        return $featureRequest->newQuery()
            ->with('author:id,name')
            ->withVoteOf($viewer)
            ->findOrFail($featureRequest->id);
    }

    /**
     * @param  array<int, FeatureRequestStatus|FeatureRequestSort>  $cases
     * @return array<int, array{value: string, label: string}>
     */
    private function options(array $cases): array
    {
        return array_map(fn (BackedEnum $case) => ['value' => (string) $case->value, 'label' => $case->label()], $cases);
    }
}
