<?php

declare(strict_types=1);

use App\Models\FeatureRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Pest\Browser\Api\PendingAwaitablePage;
use Pest\Browser\Api\Webpage;

/**
 * axe skips aria-hidden text and text that only appears after an action, so this
 * calculates the contrast of every visible text node from its rendered colors.
 */
const CONTRAST_HELPERS = <<<'JS'
    const canvas = document.createElement('canvas');
    canvas.width = canvas.height = 1;
    const context = canvas.getContext('2d', { willReadFrequently: true });
    const toRgba = (color) => {
        context.clearRect(0, 0, 1, 1);
        context.fillStyle = color;
        context.fillRect(0, 0, 1, 1);
        const [r, g, b, a] = context.getImageData(0, 0, 1, 1).data;
        return { r, g, b, a: a / 255 };
    };
    const over = (top, base) => ({
        r: top.r * top.a + base.r * (1 - top.a),
        g: top.g * top.a + base.g * (1 - top.a),
        b: top.b * top.a + base.b * (1 - top.a),
        a: 1,
    });
    const background = (element) => {
        const layers = [];
        for (let current = element; current; current = current.parentElement) {
            const layer = toRgba(getComputedStyle(current).backgroundColor);
            layers.push(layer);
            if (layer.a === 1) break;
        }
        return layers.reverse().reduce((base, layer) => over(layer, base), { r: 255, g: 255, b: 255, a: 1 });
    };
    const luminance = ({ r, g, b }) => {
        const [red, green, blue] = [r, g, b].map((value) => {
            const channel = value / 255;
            return channel <= 0.04045 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4;
        });
        return 0.2126 * red + 0.7152 * green + 0.0722 * blue;
    };
    const ratioOf = (first, second) => {
        const [lighter, darker] = [luminance(first), luminance(second)].sort((a, b) => b - a);
        return (lighter + 0.05) / (darker + 0.05);
    };
JS;

const CONTRAST_FAILURES = '(() => {'.CONTRAST_HELPERS.<<<'JS'
    const failures = new Set();
    for (const element of document.querySelectorAll('body *')) {
        const hasText = [...element.childNodes].some((node) => node.nodeType === Node.TEXT_NODE && node.textContent.trim());
        if (!hasText || ['SCRIPT', 'STYLE', 'TITLE'].includes(element.tagName) || element.closest(':disabled')) continue;
        const box = element.getBoundingClientRect();
        const style = getComputedStyle(element);
        if (box.width <= 1 || box.height <= 1 || style.visibility === 'hidden') continue;
        const base = background(element);
        const text = over(toRgba(style.color), base);
        const ratio = ratioOf(text, base);
        const size = parseFloat(style.fontSize);
        const required = size >= 24 || (size >= 18.66 && parseInt(style.fontWeight, 10) >= 700) ? 3 : 4.5;
        if (ratio < required) failures.add(`${ratio.toFixed(2)}: ${element.textContent.trim().slice(0, 40)}`);
    }
    return [...failures];
})()
JS;

/**
 * WCAG 1.4.11 asks for 3:1 where a border marks a control. Nothing else measures borders.
 */
const BORDER_FAILURES = '(() => {'.CONTRAST_HELPERS.<<<'JS'
    const failures = new Set();
    for (const element of document.querySelectorAll('button, a[data-slot="button"], input:not([type="hidden"]), textarea')) {
        const style = getComputedStyle(element);
        const box = element.getBoundingClientRect();
        const color = toRgba(style.borderTopColor);
        if (box.width <= 1 || box.height <= 1 || parseFloat(style.borderTopWidth) === 0 || color.a === 0) continue;
        const base = background(element.parentElement);
        const ratio = ratioOf(over(color, base), base);
        if (ratio < 3) failures.add(`${ratio.toFixed(2)}: ${element.textContent.trim().slice(0, 40) || element.name}`);
    }
    return [...failures];
})()
JS;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->demo = User::query()->where('email', 'demo@featureboard.test')->sole();
});

dataset('color schemes', ['light' => false, 'dark' => true]);

function visitInScheme(string $path, bool $darkMode): PendingAwaitablePage|Webpage
{
    $page = visit($path);

    return $darkMode ? $page->inDarkMode() : $page;
}

test('all text on public pages has enough contrast', function (string $path, string $heading, bool $darkMode) {
    $page = visitInScheme($path, $darkMode)->assertSee($heading);

    expect($page->script(CONTRAST_FAILURES))->toBe([]);
})->with([
    'products' => ['/', 'Produkte'],
    'requests' => ['/products/rechnungsbox', 'Rechnungsbox'],
    'roadmap' => ['/products/rechnungsbox/roadmap', 'Roadmap'],
    'login' => ['/login', 'Bei deinem Konto anmelden'],
    'register' => ['/register', 'Konto erstellen'],
])->with('color schemes');

test('all text on pages for accounts has enough contrast', function (string $path, string $heading, bool $darkMode) {
    $this->actingAs($this->demo);

    $page = visitInScheme($path, $darkMode)->assertSee($heading);

    expect($page->script(CONTRAST_FAILURES))->toBe([]);
})->with([
    'requests with votes' => ['/products/rechnungsbox', 'Rechnungsbox'],
    'form' => ['/products/rechnungsbox/requests/create', 'Pflichtfelder'],
    'profile with danger zone' => ['/settings/profile', 'Konto löschen'],
    'security' => ['/settings/security', 'Passwort ändern'],
    'appearance' => ['/settings/appearance', 'Hell'],
])->with('color schemes');

test('the message after requesting a reset link has enough contrast', function (bool $darkMode) {
    $page = visitInScheme('/forgot-password', $darkMode)
        ->fill('email', 'demo@featureboard.test')
        ->press('Link zum Zurücksetzen senden')
        ->assertSee('Wir haben dir einen Link');

    expect($page->script(CONTRAST_FAILURES))->toBe([]);
})->with('color schemes');

test('validation errors have enough contrast', function (bool $darkMode) {
    $this->actingAs($this->demo);

    $page = visitInScheme('/products/rechnungsbox/requests/create', $darkMode)
        ->press('Wunsch einreichen')
        ->assertSee('Bitte gib Titel an.');

    expect($page->script(CONTRAST_FAILURES))->toBe([]);
})->with('color schemes');

test('the delete dialog has enough contrast', function (bool $darkMode) {
    $this->actingAs($this->demo);

    $page = visitInScheme('/settings/profile', $darkMode)
        ->assertSee('Konto löschen')
        ->click('[data-test="delete-user-button"]')
        ->assertSee('wirklich löschen');

    expect($page->script(CONTRAST_FAILURES))->toBe([]);
})->with('color schemes');

test('the status control and every badge have enough contrast', function (bool $darkMode) {
    $this->actingAs(User::query()->where('is_admin', true)->sole());
    $featureRequest = FeatureRequest::query()->where('title', 'Warteliste für ausgebuchte Tage')->sole();

    $page = visitInScheme("/products/terminzeit/requests/{$featureRequest->id}", $darkMode)->assertSee('Status ändern');

    expect($page->script(CONTRAST_FAILURES))->toBe([]);
})->with('color schemes');

test('the borders of controls have enough contrast', function (string $path, string $heading, bool $darkMode) {
    $this->actingAs(User::query()->where('is_admin', true)->sole());
    $featureRequest = FeatureRequest::query()->where('title', 'Warteliste für ausgebuchte Tage')->sole();

    $page = visitInScheme(str_replace('{id}', (string) $featureRequest->id, $path), $darkMode)->assertSee($heading);

    expect($page->script(BORDER_FAILURES))->toBe([]);
})->with([
    'status buttons' => ['/products/terminzeit/requests/{id}', 'Status ändern'],
    'requests and pagination' => ['/products/rechnungsbox', 'Rechnungsbox'],
    'form fields' => ['/products/rechnungsbox/requests/create', 'Pflichtfelder'],
    'settings' => ['/settings/profile', 'Konto löschen'],
])->with('color schemes');

test('the sign-in prompt for guests has a visible border', function (bool $darkMode) {
    $page = visitInScheme('/products/rechnungsbox', $darkMode)->assertSee('Rechnungsbox');

    expect($page->script(BORDER_FAILURES))->toBe([]);
})->with('color schemes');
