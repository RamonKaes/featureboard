<?php

declare(strict_types=1);

test('the application speaks German', function () {
    expect(app()->getLocale())->toBe('de');
});

test('validation errors are shown in German', function () {
    $this->from(route('register'))
        ->post(route('register.store'), ['name' => '', 'email' => 'not-an-email'])
        ->assertSessionHasErrors([
            'name' => 'Bitte gib Name an.',
            'email' => 'E-Mail muss eine gültige E-Mail-Adresse sein.',
        ]);
});

test('failed logins are explained in German', function () {
    $this->from(route('login'))
        ->post(route('login.store'), ['email' => 'nobody@example.com', 'password' => 'wrong'])
        ->assertSessionHasErrors(['email' => 'Diese Zugangsdaten sind nicht korrekt.']);
});
