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

test('a user votes from the hint instead of submitting a duplicate', function () {
    $user = User::query()->where('email', 'demo@featureboard.test')->sole();
    $featureRequest = FeatureRequest::query()->where('title', 'Größere Logos im Rechnungskopf')->sole();
    $before = $featureRequest->votes_count;
    $button = '[aria-label^="Für „Größere Logos im Rechnungskopf“ abstimmen"]';
    $submissions = FeatureRequest::query()->count();

    $this->actingAs($user);

    $page = visit('/products/rechnungsbox/requests/create')
        ->fill('description', 'Mein halb geschriebener Text')
        ->type('title', 'Groesse der Logos')
        ->assertSee('Dann stimme stattdessen ab')
        ->click($button)
        ->wait(0.5);

    expect($page->script("document.querySelector('{$button}').getAttribute('aria-pressed')"))->toBe('true')
        ->and($page->script("document.querySelector('{$button}').getAttribute('aria-label')"))->toContain(($before + 1).' Stimme')
        ->and($page->script("document.getElementById('title').value"))->toBe('Groesse der Logos')
        ->and($page->script("document.getElementById('description').value"))->toBe('Mein halb geschriebener Text')
        ->and($featureRequest->fresh()?->votes_count)->toBe($before + 1)
        ->and($featureRequest->votes()->where('user_id', $user->id)->exists())->toBeTrue()
        ->and(FeatureRequest::query()->count())->toBe($submissions);

    $page->click($button)->wait(0.5);

    expect($page->script("document.querySelector('{$button}').getAttribute('aria-pressed')"))->toBe('false')
        ->and($featureRequest->fresh()?->votes_count)->toBe($before);
});

test('editing an own request shows similar ones without asking to vote instead', function () {
    $featureRequest = FeatureRequest::query()->where('title', 'Größere Logos im Rechnungskopf')->sole();
    $this->actingAs($featureRequest->author);

    visit("/products/rechnungsbox/requests/{$featureRequest->id}/edit")
        ->assertSee('Wunsch bearbeiten')
        ->type('title', 'Rechnungen versenden')
        ->assertSee('Ähnliche Wünsche')
        ->assertDontSee('stattdessen');
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

test('voting keeps the focus on the button after the response has arrived', function () {
    $this->actingAs(User::query()->where('email', 'demo@featureboard.test')->sole());

    $selector = "button[aria-label^='Für „Warteliste']";
    $page = visit('/products/terminzeit')->assertSee('Warteliste')->click($selector)
        ->assertAttribute($selector, 'aria-pressed', 'true')
        ->wait(1);

    expect($page->script('document.activeElement.getAttribute("aria-label")'))->toStartWith('Für „Warteliste')
        ->and($page->script('document.activeElement.getAttribute("aria-busy")'))->toBe('false');
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

function assertFocusOnInvalidField(mixed $page, string $field, string $message): void
{
    $page->assertSee($message)->wait(0.3);

    expect($page->script('document.activeElement.id'))->toBe($field)
        ->and($page->script('document.activeElement.getAttribute("aria-invalid")'))->toBe('true')
        ->and($page->script('document.getElementById(document.activeElement.getAttribute("aria-describedby"))?.textContent'))->toContain($message);
}

test('a failed login moves the focus to the field and explains the error', function () {
    $page = visit('/login')
        ->fill('email', 'nobody@example.com')
        ->fill('password', 'wrong-password')
        ->press('Anmelden');

    assertFocusOnInvalidField($page, 'email', 'Diese Zugangsdaten sind nicht korrekt.');
});

test('a failed registration moves the focus to the first invalid field', function () {
    $page = visit('/register')
        ->fill('name', 'Someone')
        ->fill('email', 'demo@featureboard.test')
        ->fill('password', 'a-long-password')
        ->fill('password_confirmation', 'a-long-password')
        ->click('button[type=submit]');

    assertFocusOnInvalidField($page, 'email', 'E-Mail ist bereits vergeben.');
});

test('a failed reset request moves the focus to the field', function () {
    $page = visit('/forgot-password')
        ->fill('email', 'nobody@example.com')
        ->press('Link zum Zurücksetzen senden');

    assertFocusOnInvalidField($page, 'email', 'Wir konnten keinen Account');
});

test('a failed password reset moves the focus to the field with the error', function () {
    $page = visit('/reset-password/some-token?email=demo@featureboard.test')
        ->fill('password', 'a-long-password')
        ->fill('password_confirmation', 'another-password')
        ->click('button[type=submit]');

    assertFocusOnInvalidField($page, 'email', 'Dieser Link zum Zurücksetzen ist ungültig.');
});

test('very long words do not overflow any page', function (int $width) {
    $author = User::factory()->create(['name' => str_repeat('n', 255)]);
    $product = Product::query()->where('slug', 'terminzeit')->sole();
    $featureRequest = FeatureRequest::factory()->for($product)->for($author, 'author')->create([
        'title' => str_repeat('t', 120),
        'description' => str_repeat('d', 2000),
        'status' => FeatureRequestStatus::Planned,
        'votes_count' => 99,
    ]);

    foreach (['/products/terminzeit', "/products/terminzeit/requests/{$featureRequest->id}", '/products/terminzeit/roadmap'] as $path) {
        $page = visit($path)->resize($width, 800)->assertSee('Terminzeit');

        expect($page->script('document.documentElement.scrollWidth - document.documentElement.clientWidth'))->toBe(0, $path);
    }
})->with([320, 1280]);

test('the appearance choice announces its state', function () {
    $this->actingAs(User::query()->where('email', 'demo@featureboard.test')->sole());

    $page = visit('/settings/appearance')->assertSee('Hell')->click('Dunkel');

    expect($page->script('[...document.querySelectorAll("main button[aria-pressed]")].map(b => b.textContent.trim() + "=" + b.getAttribute("aria-pressed")).join()'))
        ->toBe('Hell=false,Dunkel=true,System=false');
});

test('errors in the settings are tied to their field', function (string $path, string $field, array $fill, string $submit, string $message) {
    $this->actingAs(User::query()->where('email', 'demo@featureboard.test')->sole());

    $page = visit($path)->assertNoJavaScriptErrors();

    if ($field === 'password' && $path === '/settings/profile') {
        $page->click('[data-test="delete-user-button"]')->assertSee('wirklich löschen');
    }

    foreach ($fill as $name => $value) {
        $page->fill($name, $value);
    }

    $page->click($submit)->assertSee($message)->wait(0.3);

    expect($page->script("document.getElementById('{$field}').getAttribute('aria-invalid')"))->toBe('true')
        ->and($page->script("document.getElementById(document.getElementById('{$field}').getAttribute('aria-describedby'))?.textContent"))->toContain($message);
})->with([
    'taken email' => ['/settings/profile', 'email', ['email' => 'admin@featureboard.test'], '[data-test="update-profile-button"]', 'bereits'],
    'wrong current password' => ['/settings/security', 'current_password', ['current_password' => 'wrong-password', 'password' => 'new-password', 'password_confirmation' => 'new-password'], '[data-test="update-password-button"]', 'Passwort'],
    'wrong password when deleting' => ['/settings/profile', 'password', ['password' => 'wrong-password'], '[data-test="confirm-delete-user-button"]', 'Passwort'],
]);
