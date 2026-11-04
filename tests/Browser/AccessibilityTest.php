<?php

declare(strict_types=1);

use App\Models\FeatureRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function demoUser(): User
{
    return User::query()->where('email', 'demo@featureboard.test')->sole();
}

/**
 * Sequences survive rolled back tests, so ids must be looked up rather than assumed.
 */
function requestPath(string $title, string $suffix = ''): string
{
    $featureRequest = FeatureRequest::query()->with('product')->where('title', $title)->sole();

    return "/products/{$featureRequest->product->slug}/requests/{$featureRequest->id}{$suffix}";
}

function assertAccessible(string $path, string $heading, bool $darkMode): void
{
    $page = visit($path);

    if ($darkMode) {
        $page = $page->inDarkMode();
    }

    // The heading proves that the intended page was checked rather than an error page.
    $page->assertSee($heading)->assertNoAccessibilityIssues(3);

    expect($page->script('document.querySelectorAll("h1").length'))->toBe(1)
        ->and($page->script('document.documentElement.lang'))->toBe('de');
}

dataset('color schemes', ['light' => false, 'dark' => true]);

test('public pages have no accessibility issues', function (Closure $path, string $heading, bool $darkMode) {
    assertAccessible($path(), $heading, $darkMode);
})->with([
    'products' => [fn () => '/', 'Produkte'],
    'request list' => [fn () => '/products/rechnungsbox', 'Rechnungsbox'],
    'filtered list' => [fn () => '/products/rechnungsbox?status=done', 'Rechnungsbox'],
    'roadmap' => [fn () => '/products/rechnungsbox/roadmap', 'Roadmap für Rechnungsbox'],
    'request' => [fn () => requestPath('Mahnungen in mehreren Stufen'), 'Mahnungen in mehreren Stufen'],
    'login' => [fn () => '/login', 'Bei deinem Konto anmelden'],
    'register' => [fn () => '/register', 'Konto erstellen'],
    'forgot password' => [fn () => '/forgot-password', 'Passwort vergessen'],
    'reset password' => [fn () => '/reset-password/some-token?email=demo@featureboard.test', 'Passwort zurücksetzen'],
])->with('color schemes');

test('pages for users have no accessibility issues', function (Closure $path, string $heading, bool $darkMode) {
    $this->actingAs(demoUser());

    assertAccessible($path(), $heading, $darkMode);
})->with([
    'request list' => [fn () => '/products/rechnungsbox', 'Rechnungsbox'],
    'voted request' => [fn () => requestPath('E-Rechnung im Format ZUGFeRD erstellen'), 'E-Rechnung im Format ZUGFeRD erstellen'],
    'own request' => [fn () => requestPath('Wiederkehrende Rechnungen automatisch versenden'), 'Wiederkehrende Rechnungen automatisch versenden'],
    'finished request' => [fn () => requestPath('Rechnungen als CSV für die Steuerberatung exportieren'), 'Rechnungen als CSV für die Steuerberatung exportieren'],
    'create form' => [fn () => '/products/rechnungsbox/requests/create', 'Wunsch für Rechnungsbox einreichen'],
    'edit form' => [fn () => requestPath('Wiederkehrende Rechnungen automatisch versenden', '/edit'), 'Wunsch bearbeiten'],
    'profile' => [fn () => '/settings/profile', 'Einstellungen'],
    'security' => [fn () => '/settings/security', 'Einstellungen'],
    'appearance' => [fn () => '/settings/appearance', 'Einstellungen'],
])->with('color schemes');

test('the status controls for admins have no accessibility issues', function (bool $darkMode) {
    $this->actingAs(User::query()->where('email', 'admin@featureboard.test')->sole());

    assertAccessible(requestPath('Mahnungen in mehreren Stufen'), 'Mahnungen in mehreren Stufen', $darkMode);
})->with('color schemes');

test('the hint with vote buttons has no accessibility issues', function (bool $darkMode) {
    $this->actingAs(demoUser());

    $page = visit('/products/rechnungsbox/requests/create');

    ($darkMode ? $page->inDarkMode() : $page)
        ->type('title', 'Rechnungen Mahnungen')
        ->assertSee('Dann stimme stattdessen ab')
        ->assertNoAccessibilityIssues(3);
})->with('color schemes');

test('form errors have no accessibility issues', function () {
    $this->actingAs(demoUser());

    visit('/products/rechnungsbox/requests/create')
        ->fill('title', 'Abc')
        ->click('button[type=submit]')
        ->assertSee('Titel muss mindestens 5 Zeichen lang sein.')
        ->assertNoAccessibilityIssues(3);
});

test('the error page has no accessibility issues', function (bool $darkMode) {
    assertAccessible('/products/unknown', 'Seite nicht gefunden', $darkMode);
})->with('color schemes');
