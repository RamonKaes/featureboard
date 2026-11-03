<?php

declare(strict_types=1);

use App\Enums\FeatureRequestStatus;
use App\Models\FeatureRequest;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('a user logs in, opens a product and votes', function () {
    $featureRequest = FeatureRequest::query()->where('title', 'Warteliste für ausgebuchte Tage')->sole();
    $before = $featureRequest->votes_count;

    visit('/login')
        ->fill('email', 'demo@featureboard.test')
        ->fill('password', 'password')
        ->press('Anmelden')
        ->assertSee('Produkte')
        ->click('Terminzeit')
        ->assertSee('Warteliste für ausgebuchte Tage')
        ->click("button[aria-label^='Für „Warteliste']")
        ->assertAttribute("button[aria-label^='Für „Warteliste']", 'aria-pressed', 'true');

    expect($featureRequest->fresh()?->votes_count)->toBe($before + 1);
});

test('a user sees similar requests while typing and submits a new one', function () {
    $this->actingAs(User::query()->where('email', 'demo@featureboard.test')->sole());

    visit('/products/rechnungsbox/requests/create')
        ->type('title', 'Groesse der Logos')
        ->assertSee('Größere Logos im Rechnungskopf')
        ->fill('description', 'Bitte die Größe frei einstellbar machen.')
        ->press('Wunsch einreichen')
        ->assertSee('Dein Wunsch wurde eingereicht.');

    expect(FeatureRequest::query()->where('title', 'Groesse der Logos')->exists())->toBeTrue();
});

test('an admin moves a request through the workflow', function () {
    $this->actingAs(User::query()->where('email', 'admin@featureboard.test')->sole());
    $featureRequest = FeatureRequest::query()->where('title', 'Warteliste für ausgebuchte Tage')->sole();

    visit("/products/terminzeit/requests/{$featureRequest->id}")
        ->assertSee('Status ändern')
        ->press('Geplant')
        ->assertSee('Status geändert: Geplant')
        ->assertSee('In Arbeit');

    expect($featureRequest->fresh()?->status)->toBe(FeatureRequestStatus::Planned);
});

test('html in user text is displayed and not executed', function () {
    $featureRequest = FeatureRequest::factory()->for(Product::query()->where('slug', 'terminzeit')->sole())->create([
        'title' => 'Bild <img src=x onerror="window.hacked=true">',
        'description' => '<script>window.hacked=true</script>',
    ]);

    $page = visit("/products/terminzeit/requests/{$featureRequest->id}")
        ->assertSee('<script>window.hacked=true</script>');

    expect($page->script('window.hacked === undefined'))->toBeTrue();
});

test('the skip link comes first and moves the focus to the content', function () {
    $page = visit('/products/terminzeit')->assertSee('Terminzeit');

    expect($page->script('document.querySelector("a, button, input, select, textarea").textContent.trim()'))
        ->toBe('Zum Inhalt springen');

    $page->keys('a[href="#main-content"]', 'Enter');

    expect($page->script('document.activeElement.id'))->toBe('main-content');
});

test('a page change moves the focus to the new content', function () {
    $page = visit('/')->assertSee('Produkte')->click('Terminzeit')->assertSee('Alle Produkte');

    expect($page->script('document.activeElement.id'))->toBe('main-content');
});

test('voting keeps the focus on the button', function () {
    $this->actingAs(User::query()->where('email', 'demo@featureboard.test')->sole());

    $selector = "button[aria-label^='Für „Warteliste']";
    $page = visit('/products/terminzeit')->assertSee('Warteliste')->click($selector)
        ->assertAttribute($selector, 'aria-pressed', 'true');

    expect($page->script('document.activeElement.getAttribute("aria-pressed")'))->toBe('true');
});

test('a status change keeps the focus in the status area', function () {
    $this->actingAs(User::query()->where('email', 'admin@featureboard.test')->sole());
    $featureRequest = FeatureRequest::query()->where('title', 'Warteliste für ausgebuchte Tage')->sole();

    $page = visit("/products/terminzeit/requests/{$featureRequest->id}")
        ->assertSee('Status ändern')
        ->press('Geplant')
        ->assertSee('Status geändert: Geplant');

    expect($page->script('document.activeElement.id'))->toBe('status-changer-heading');
});

test('a final status moves the focus to the page heading', function () {
    $this->actingAs(User::query()->where('email', 'admin@featureboard.test')->sole());
    $featureRequest = FeatureRequest::query()->where('status', FeatureRequestStatus::InProgress)->firstOrFail();

    $page = visit("/products/{$featureRequest->product()->firstOrFail()->slug}/requests/{$featureRequest->id}")
        ->assertSee('Status ändern')
        ->press('Erledigt')
        ->assertSee('Status geändert: Erledigt');

    expect($page->script('document.activeElement.tagName'))->toBe('H1');
});

test('the description survives typing in the title, in either order', function () {
    $this->actingAs(User::query()->where('email', 'demo@featureboard.test')->sole());

    $page = visit('/products/rechnungsbox/requests/create')->assertSee('Alle Felder sind Pflichtfelder.');

    $page->type('description', 'Erst die Beschreibung')->type('title', 'Dann der Titel');

    expect($page->value('#description'))->toBe('Erst die Beschreibung')
        ->and($page->value('#title'))->toBe('Dann der Titel');
});

test('typed text survives a validation error', function () {
    $this->actingAs(User::query()->where('email', 'demo@featureboard.test')->sole());

    $page = visit('/products/rechnungsbox/requests/create')
        ->type('title', 'Abc')
        ->type('description', 'Eine ausreichend lange Beschreibung')
        ->press('Wunsch einreichen')
        ->assertSee('Titel muss mindestens 5 Zeichen lang sein.');

    expect($page->value('#description'))->toBe('Eine ausreichend lange Beschreibung')
        ->and($page->value('#title'))->toBe('Abc');
});

test('an edit keeps a changed description when the title changes too', function () {
    $demo = User::query()->where('email', 'demo@featureboard.test')->sole();
    $this->actingAs($demo);
    $featureRequest = FeatureRequest::query()->where('title', 'Wiederkehrende Rechnungen automatisch versenden')->sole();

    visit("/products/rechnungsbox/requests/{$featureRequest->id}/edit")
        ->assertSee('Wunsch bearbeiten')
        ->fill('description', 'Komplett neu geschriebene Beschreibung')
        ->type('title', 'Wiederkehrende Rechnungen versenden')
        ->press('Speichern')
        ->assertSee('Dein Wunsch wurde gespeichert.');

    expect($featureRequest->fresh()?->description)->toBe('Komplett neu geschriebene Beschreibung')
        ->and($featureRequest->fresh()?->title)->toBe('Wiederkehrende Rechnungen versenden');
});
