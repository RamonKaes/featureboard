<?php

declare(strict_types=1);

use App\Http\Controllers\FeatureRequestController;
use App\Http\Controllers\FeatureRequestStatusController;
use App\Http\Controllers\FeatureRequestVoteController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoadmapController;
use App\Http\Controllers\SimilarFeatureRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProductController::class, 'index'])->name('home');
Route::get('products/{product:slug}', [FeatureRequestController::class, 'index'])->name('products.show');
Route::get('products/{product:slug}/roadmap', RoadmapController::class)->name('products.roadmap');

Route::scopeBindings()->prefix('products/{product:slug}/requests')->name('feature-requests.')->group(function () {
    Route::middleware('auth')->group(function () {
        Route::get('create', [FeatureRequestController::class, 'create'])->name('create');
        Route::get('similar', SimilarFeatureRequestController::class)->middleware('throttle:search')->name('similar');
        Route::post('/', [FeatureRequestController::class, 'store'])->name('store');
        Route::get('{featureRequest}/edit', [FeatureRequestController::class, 'edit'])
            ->can('update', 'featureRequest')
            ->name('edit');
        Route::put('{featureRequest}', [FeatureRequestController::class, 'update'])
            ->can('update', 'featureRequest')
            ->name('update');
    });

    Route::get('{featureRequest}', [FeatureRequestController::class, 'show'])->name('show');
});

Route::middleware('auth')->group(function () {
    Route::middleware('throttle:votes')->group(function () {
        Route::post('requests/{featureRequest}/vote', [FeatureRequestVoteController::class, 'store'])->name('votes.store');
        Route::delete('requests/{featureRequest}/vote', [FeatureRequestVoteController::class, 'destroy'])->name('votes.destroy');
    });

    Route::put('requests/{featureRequest}/status', FeatureRequestStatusController::class)->name('feature-requests.status.update');
});

require __DIR__.'/settings.php';
