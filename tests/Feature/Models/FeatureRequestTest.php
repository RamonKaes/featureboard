<?php

declare(strict_types=1);

use App\Enums\FeatureRequestStatus;
use App\Models\FeatureRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

test('a feature request belongs to a product and an author', function () {
    $product = Product::factory()->create();
    $author = User::factory()->create();

    $featureRequest = FeatureRequest::factory()->for($product)->for($author, 'author')->create();

    expect($featureRequest->product->is($product))->toBeTrue()
        ->and($featureRequest->author?->is($author))->toBeTrue()
        ->and($author->featureRequests->sole()->is($featureRequest))->toBeTrue();
});

test('a new feature request starts with status new and no votes', function () {
    $featureRequest = new FeatureRequest;

    expect($featureRequest->status)->toBe(FeatureRequestStatus::New)
        ->and($featureRequest->votes_count)->toBe(0);
});

test('the database applies the same defaults', function () {
    $id = DB::table('feature_requests')->insertGetId([
        'product_id' => Product::factory()->create()->id,
        'title' => 'Title',
        'description' => 'Description',
    ]);

    $featureRequest = FeatureRequest::query()->findOrFail($id);

    expect($featureRequest->status)->toBe(FeatureRequestStatus::New)
        ->and($featureRequest->votes_count)->toBe(0);
});

test('status and vote count cannot be mass assigned', function () {
    $featureRequest = new FeatureRequest([
        'status' => FeatureRequestStatus::Done,
        'votes_count' => 99,
    ]);

    expect($featureRequest->status)->toBe(FeatureRequestStatus::New)
        ->and($featureRequest->votes_count)->toBe(0);
});

test('the database rejects an unknown status', function () {
    $featureRequest = FeatureRequest::factory()->create();

    expect(fn () => DB::table('feature_requests')
        ->where('id', $featureRequest->id)
        ->update(['status' => 'archived']))
        ->toThrow(QueryException::class, 'feature_requests_status_check');
});

test('the database rejects a negative vote count', function () {
    $featureRequest = FeatureRequest::factory()->create();

    expect(fn () => DB::table('feature_requests')
        ->where('id', $featureRequest->id)
        ->update(['votes_count' => -1]))
        ->toThrow(QueryException::class, 'feature_requests_votes_count_not_negative');
});

test('a feature request stays anonymously when its author is deleted', function () {
    $featureRequest = FeatureRequest::factory()->create();

    $featureRequest->author?->delete();

    expect($featureRequest->fresh()?->user_id)->toBeNull();
});
