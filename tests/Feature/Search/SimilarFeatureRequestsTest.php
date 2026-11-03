<?php

declare(strict_types=1);

use App\Models\FeatureRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function findSimilar(Product $product, string $title, ?int $except = null): array
{
    return test()->actingAs(User::factory()->create())
        ->getJson(route('feature-requests.similar', [$product, 'title' => $title, 'except' => $except]))
        ->assertOk()
        ->json();
}

function titlesOf(array $results): array
{
    return array_column($results, 'title');
}

beforeEach(function () {
    $this->product = Product::factory()->create();
});

test('it finds other word forms and spellings', function (string $stored, string $typed) {
    FeatureRequest::factory()->for($this->product)->create(['title' => $stored, 'description' => 'Ohne Bezug.']);

    expect(titlesOf(findSimilar($this->product, $typed)))->toBe([$stored]);
})->with([
    'plural' => ['Rechnungen automatisch versenden', 'Rechnung'],
    'singular' => ['Mahnung in mehreren Stufen', 'Mahnungen'],
    'umlaut' => ['Größere Logos im Kopfbereich', 'Größe'],
    'umlaut written with oe' => ['Größere Logos im Kopfbereich', 'Groesse'],
    'umlaut written with ue' => ['Rückgaben einbuchen', 'Rueckgabe'],
    'umlaut without dots' => ['Rückgaben einbuchen', 'Ruckgabe'],
    'sharp s' => ['Maßeinheit wählen', 'Masseinheit'],
    'unfinished last word' => ['Warteliste für ausgebuchte Tage', 'Wartel'],
    'upper and lower case' => ['Export als PDF', 'EXPORT'],
]);

test('it needs only one matching word', function () {
    FeatureRequest::factory()->for($this->product)->create(['title' => 'Mahnungen erstellen', 'description' => 'Ohne Bezug.']);

    expect(titlesOf(findSimilar($this->product, 'Mahnung per Brief versenden')))->toBe(['Mahnungen erstellen']);
});

test('it searches the description too', function () {
    FeatureRequest::factory()->for($this->product)->create(['title' => 'Ohne Bezug', 'description' => 'Eine Warteliste wäre hilfreich.']);

    expect(titlesOf(findSimilar($this->product, 'Warteliste')))->toBe(['Ohne Bezug']);
});

test('a match in the title ranks above a match in the description', function () {
    FeatureRequest::factory()->for($this->product)->create(['title' => 'Treffer im Text', 'description' => 'Es geht um Mahnungen.']);
    FeatureRequest::factory()->for($this->product)->create(['title' => 'Mahnungen erstellen', 'description' => 'Ohne Bezug.']);

    expect(titlesOf(findSimilar($this->product, 'Mahnung')))->toBe(['Mahnungen erstellen', 'Treffer im Text']);
});

test('more matching words rank higher', function () {
    FeatureRequest::factory()->for($this->product)->create(['title' => 'Rechnungen anzeigen', 'description' => 'Ohne Bezug.']);
    FeatureRequest::factory()->for($this->product)->create(['title' => 'Rechnungen automatisch versenden', 'description' => 'Ohne Bezug.']);

    expect(titlesOf(findSimilar($this->product, 'Rechnung automatisch')))
        ->toBe(['Rechnungen automatisch versenden', 'Rechnungen anzeigen']);
});

test('unrelated requests are not found', function () {
    FeatureRequest::factory()->for($this->product)->create(['title' => 'Warteliste', 'description' => 'Ohne Bezug.']);

    expect(findSimilar($this->product, 'Barcode'))->toBe([]);
});

test('only requests of the same product are found', function () {
    FeatureRequest::factory()->create(['title' => 'Mahnungen erstellen']);

    expect(findSimilar($this->product, 'Mahnung'))->toBe([]);
});

test('the edited request itself is excluded', function () {
    $own = FeatureRequest::factory()->for($this->product)->create(['title' => 'Mahnungen erstellen']);
    FeatureRequest::factory()->for($this->product)->create(['title' => 'Mahnungen versenden']);

    expect(titlesOf(findSimilar($this->product, 'Mahnung', $own->id)))->toBe(['Mahnungen versenden']);
});

test('at most five results are returned', function () {
    FeatureRequest::factory()->count(7)->for($this->product)->create(['title' => 'Mahnungen erstellen']);

    expect(findSimilar($this->product, 'Mahnung'))->toHaveCount(5);
});

test('results contain only what the hint needs', function () {
    $featureRequest = FeatureRequest::factory()->for($this->product)->create(['title' => 'Mahnungen erstellen', 'votes_count' => 3]);

    expect(findSimilar($this->product, 'Mahnung'))->toBe([[
        'id' => $featureRequest->id,
        'title' => 'Mahnungen erstellen',
        'status' => ['value' => 'new', 'label' => 'Neu'],
        'votesCount' => 3,
    ]]);
});

test('input without searchable words returns nothing', function (string $title) {
    FeatureRequest::factory()->for($this->product)->create(['title' => 'Mahnungen erstellen']);

    expect(findSimilar($this->product, $title))->toBe([]);
})->with([
    'only stop words' => ['und der die'],
    'only punctuation' => ['!!! ???'],
]);

test('search operators in the input cannot break the query', function (string $title) {
    FeatureRequest::factory()->for($this->product)->create(['title' => 'Mahnungen erstellen']);

    expect(titlesOf(findSimilar($this->product, $title)))->toBe(['Mahnungen erstellen']);
})->with([
    "Mahnung'",
    'Mahnung & | ! ( ) : *',
    "Mahnung'; DROP TABLE votes; --",
    '<script>Mahnung</script>',
]);

test('the title is required and limited', function (string $title) {
    $this->actingAs(User::factory()->create())
        ->getJson(route('feature-requests.similar', [$this->product, 'title' => $title]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('title');
})->with(['', str_repeat('a', 121)]);

test('guests cannot search', function () {
    $this->getJson(route('feature-requests.similar', [$this->product, 'title' => 'Mahnung']))->assertUnauthorized();
});

test('the search vector follows changes to the title', function () {
    $featureRequest = FeatureRequest::factory()->for($this->product)->create(['title' => 'Mahnungen erstellen', 'description' => 'Ohne Bezug.']);

    $featureRequest->update(['title' => 'Warteliste anlegen']);

    expect(findSimilar($this->product, 'Mahnung'))->toBe([])
        ->and(titlesOf(findSimilar($this->product, 'Warteliste')))->toBe(['Warteliste anlegen']);
});

test('the search can use the GIN index', function () {
    DB::statement('SET LOCAL enable_seqscan = off');

    $plan = FeatureRequest::query()->similarTo('Mahnung')->explain()->pluck('QUERY PLAN')->implode("\n");

    expect($plan)->toContain('feature_requests_search_vector_index');
});

test('the search vector is not sent to the frontend', function () {
    $featureRequest = FeatureRequest::factory()->for($this->product)->create();

    expect($featureRequest->fresh()?->toArray())->not->toHaveKey('search_vector');
});
