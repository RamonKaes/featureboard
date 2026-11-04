<?php

declare(strict_types=1);

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();
    expect($user->fresh())->toBeNull();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull();
});

test('an email address with capitals is stored in lowercase, so the login still works', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), ['name' => $user->name, 'email' => 'New.Address@Example.com'])
        ->assertRedirect();

    expect($user->fresh()?->email)->toBe('new.address@example.com');

    auth()->logout();
    $this->flushSession();

    $this->post(route('login.store'), ['email' => 'New.Address@Example.com', 'password' => 'password'])
        ->assertRedirect();

    $this->assertAuthenticatedAs($user->fresh());
});

test('an address differing only in case counts as taken', function () {
    User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), ['name' => $user->name, 'email' => 'TAKEN@example.com'])
        ->assertSessionHasErrors('email');

    expect($user->fresh()?->email)->not->toBe('taken@example.com');
});

test('name and email are limited to what the database column promises', function (string $field, string $value, bool $allowed) {
    $user = User::factory()->create();
    $input = ['name' => 'Someone', 'email' => 'someone@example.com', $field => $value];

    $response = $this->actingAs($user)->patch(route('profile.update'), $input);

    $allowed ? $response->assertSessionHasNoErrors() : $response->assertSessionHasErrors($field);
})->with([
    '255 character name' => ['name', str_repeat('a', 255), true],
    '256 character name' => ['name', str_repeat('a', 256), false],
    '255 character email' => ['email', str_repeat('a', 243).'@example.com', true],
    '256 character email' => ['email', str_repeat('a', 244).'@example.com', false],
]);
