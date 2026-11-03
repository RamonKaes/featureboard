<?php

declare(strict_types=1);

use App\Models\User;

test('users are not admins by default', function () {
    expect(User::factory()->create()->is_admin)->toBeFalse();
});

test('the admin factory state creates an admin', function () {
    expect(User::factory()->admin()->create()->is_admin)->toBeTrue();
});

test('the admin flag cannot be mass assigned', function () {
    expect((new User(['is_admin' => true]))->is_admin)->toBeFalse();
});

test('registration cannot grant admin rights', function () {
    $this->post(route('register.store'), [
        'name' => 'Mallory',
        'email' => 'mallory@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'is_admin' => true,
    ]);

    expect(User::query()->where('email', 'mallory@example.com')->sole()->is_admin)->toBeFalse();
});
