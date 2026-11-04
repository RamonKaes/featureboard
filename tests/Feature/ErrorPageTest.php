<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

test('error pages are German and lead back to the start', function (int $status, string $heading) {
    Route::get('/test-error', fn () => abort($status));

    $this->get('/test-error')
        ->assertStatus($status)
        ->assertSee($heading)
        ->assertSee('<html lang="de"', false)
        ->assertSee('href="'.route('home').'"', false);
})->with([
    [400, 'Ungültige Anfrage'],
    [403, 'Kein Zugriff'],
    [404, 'Seite nicht gefunden'],
    [419, 'Sitzung abgelaufen'],
    [429, 'Zu viele Anfragen'],
    [500, 'Das hat nicht geklappt'],
    [503, 'Gerade nicht erreichbar'],
]);

test('a status without its own text still gets a German page', function () {
    $this->post('/products')->assertStatus(404)->assertSee('Seite nicht gefunden');
    $this->delete('/login')->assertStatus(405)->assertSee('Das hat nicht geklappt');
});

test('an unexpected exception reveals nothing without debug mode', function () {
    config(['app.debug' => false]);
    Route::get('/test-error', fn () => throw new RuntimeException('secret detail'));

    $this->get('/test-error')
        ->assertServerError()
        ->assertSee('Das hat nicht geklappt')
        ->assertDontSee('secret detail')
        ->assertDontSee('vendor/');
});
