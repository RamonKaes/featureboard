<?php

declare(strict_types=1);

use App\Enums\FeatureRequestStatus;
use App\Models\FeatureRequest;
use App\Models\Product;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are sent to the login page', function () {
    $product = Product::factory()->create();

    $this->get(route('feature-requests.create', $product))->assertRedirect(route('login'));
    $this->post(route('feature-requests.store', $product), [])->assertRedirect(route('login'));

    expect(FeatureRequest::query()->count())->toBe(0);
});

test('logged in users see the form', function () {
    $product = Product::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('feature-requests.create', $product))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('featureRequests/Create')
            ->where('product.slug', $product->slug));
});

test('logged in users can submit a feature request', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $response = $this->actingAs($user)->post(route('feature-requests.store', $product), [
        'title' => 'Export als PDF',
        'description' => 'Bitte einen Export als PDF anbieten.',
    ]);

    $featureRequest = FeatureRequest::query()->sole();

    $response->assertRedirect(route('feature-requests.show', [$product, $featureRequest]));

    expect($featureRequest->title)->toBe('Export als PDF')
        ->and($featureRequest->product_id)->toBe($product->id)
        ->and($featureRequest->user_id)->toBe($user->id)
        ->and($featureRequest->status)->toBe(FeatureRequestStatus::New)
        ->and($featureRequest->votes_count)->toBe(0);
});

test('protected fields cannot be set through the form', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $this->actingAs($user)->post(route('feature-requests.store', $product), [
        'title' => 'Export als PDF',
        'description' => 'Bitte einen Export als PDF anbieten.',
        'status' => 'done',
        'votes_count' => 99,
        'user_id' => User::factory()->create()->id,
        'product_id' => Product::factory()->create()->id,
    ]);

    $featureRequest = FeatureRequest::query()->sole();

    expect($featureRequest->status)->toBe(FeatureRequestStatus::New)
        ->and($featureRequest->votes_count)->toBe(0)
        ->and($featureRequest->user_id)->toBe($user->id)
        ->and($featureRequest->product_id)->toBe($product->id);
});

test('invalid input is rejected with German messages', function (array $input, string $field, string $message) {
    $product = Product::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('feature-requests.store', $product), [
            'title' => 'Ein gültiger Titel',
            'description' => 'Eine gültige Beschreibung.',
            ...$input,
        ])
        ->assertSessionHasErrors([$field => $message]);

    expect(FeatureRequest::query()->count())->toBe(0);
})->with([
    'missing title' => [['title' => ''], 'title', 'Bitte gib Titel an.'],
    'short title' => [['title' => 'Abc'], 'title', 'Titel muss mindestens 5 Zeichen lang sein.'],
    'long title' => [['title' => str_repeat('a', 121)], 'title', 'Titel darf höchstens 120 Zeichen lang sein.'],
    'missing description' => [['description' => ''], 'description', 'Bitte gib Beschreibung an.'],
    'short description' => [['description' => 'Zu kurz'], 'description', 'Beschreibung muss mindestens 10 Zeichen lang sein.'],
    'invisible tag characters' => [['title' => str_repeat("\u{E0041}", 5)], 'title', 'Titel muss mindestens 3 Buchstaben oder Ziffern enthalten.'],
    'only punctuation' => [['title' => '!!!!!'], 'title', 'Titel muss mindestens 3 Buchstaben oder Ziffern enthalten.'],
    'only emoji' => [['title' => '🚀🚀🚀🚀🚀'], 'title', 'Titel muss mindestens 3 Buchstaben oder Ziffern enthalten.'],
    'right-to-left override' => [['title' => "Erledigt \u{202E}tgilredE"], 'title', 'Titel darf keine Steuerzeichen für die Schreibrichtung enthalten.'],
    'long description' => [['description' => str_repeat('a', 5001)], 'description', 'Beschreibung darf höchstens 5000 Zeichen lang sein.'],
]);

test('titles in any script with punctuation and emoji are accepted', function (string $title) {
    $this->actingAs(User::factory()->create())
        ->post(route('feature-requests.store', Product::factory()->create()), [
            'title' => $title,
            'description' => 'Eine gültige Beschreibung.',
        ])
        ->assertSessionHasNoErrors();

    expect(FeatureRequest::query()->sole()->title)->toBe($title);
})->with([
    'umlauts' => ['Größenänderung'],
    'three digits around emoji' => ['🚀 1 2 3 🚀'],
    'arabic without controls' => ['تصدير الفواتير'],
    'family emoji with joiners' => ['Export 👨‍👩‍👧 für Familien'],
]);
