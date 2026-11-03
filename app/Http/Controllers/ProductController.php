<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /**
     * Show all products.
     */
    public function index(): Response
    {
        return Inertia::render('products/Index', [
            'products' => ProductResource::collection(
                Product::query()->withCount('featureRequests')->orderBy('name')->get(),
            ),
        ]);
    }
}
