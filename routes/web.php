<?php

declare(strict_types=1);

use App\Http\Controllers\FeatureRequestController;
use App\Http\Controllers\FeatureRequestStatusController;
use App\Http\Controllers\FeatureRequestVoteController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoadmapController;
use App\Http\Controllers\SimilarFeatureRequestController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;

Route::get('/', [ProductController::class, 'index'])->name('home');
Route::get('products/{product:slug}', [FeatureRequestController::class, 'index'])->name('products.show');
Route::get('products/{product:slug}/roadmap', RoadmapController::class)->name('products.roadmap');

Route::scopeBindings()->prefix('products/{product:slug}/requests')->name('feature-requests.')->group(function () {
    Route::middleware('auth')->group(function () {
        Route::get('create', [FeatureRequestController::class, 'create'])->name('create');
        Route::get('similar', SimilarFeatureRequestController::class)->middleware('throttle:search')->name('similar');
        Route::post('/', [FeatureRequestController::class, 'store'])->middleware('throttle:submissions')->name('store');
        Route::get('{featureRequest}/edit', [FeatureRequestController::class, 'edit'])
            ->can('update', 'featureRequest')
            ->name('edit');
        Route::put('{featureRequest}', [FeatureRequestController::class, 'update'])
            ->can('update', 'featureRequest')
            ->middleware('throttle:submissions')
            ->name('update');
    });

    Route::get('{featureRequest}', [FeatureRequestController::class, 'show'])->name('show');
});

// Fortify has no rate limit for registration, so this replaces its route.
Route::post('register', [RegisteredUserController::class, 'store'])
    ->middleware(['guest', 'throttle:registrations'])
    ->name('register.store');

Route::middleware('auth')->group(function () {
    // Fortify registers these routes even though password confirmation is not used.
    Route::match(['get', 'post'], 'user/confirm-password', fn () => abort(404));
    Route::get('user/confirmed-password-status', fn () => abort(404));

    Route::middleware('throttle:votes')->group(function () {
        Route::post('requests/{featureRequest}/vote', [FeatureRequestVoteController::class, 'store'])->name('votes.store');
        Route::delete('requests/{featureRequest}/vote', [FeatureRequestVoteController::class, 'destroy'])->name('votes.destroy');
    });

    Route::put('requests/{featureRequest}/status', FeatureRequestStatusController::class)->name('feature-requests.status.update');
});

require __DIR__.'/settings.php';
