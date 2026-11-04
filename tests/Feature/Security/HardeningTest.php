<?php

declare(strict_types=1);

use App\Actions\DeleteUser;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;

test('requesting reset links is limited to 6 requests per minute', function () {
    Notification::fake();

    foreach (range(1, 6) as $attempt) {
        $this->post(route('password.email'), ['email' => "person{$attempt}@example.com"])->assertRedirect();
    }

    $this->post(route('password.email'), ['email' => 'person7@example.com'])->assertTooManyRequests();
});

test('resetting a password is limited to 6 requests per minute', function () {
    $input = ['token' => 'wrong', 'email' => 'person@example.com', 'password' => 'a-long-password', 'password_confirmation' => 'a-long-password'];

    foreach (range(1, 6) as $attempt) {
        $this->post(route('password.update'), $input)->assertRedirect();
    }

    $this->post(route('password.update'), $input)->assertTooManyRequests();
});

test('a login is refused after five failures for the same address', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
    }

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertTooManyRequests();
});

test('security headers are sent on pages, missing pages and errors', function (Closure $request, int $status) {
    Route::get('/test-error', fn () => throw new RuntimeException('failure'));

    $request($this)
        ->assertStatus($status)
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
})->with([
    'page' => [fn ($test) => $test->get('/'), 200],
    'missing page' => [fn ($test) => $test->get('/products/unknown'), 404],
    'server error' => [fn ($test) => $test->get('/test-error'), 500],
]);

test('HSTS is only sent over HTTPS', function () {
    $this->get('http://localhost/')->assertHeaderMissing('Strict-Transport-Security');
    $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
});

test('another session ends when the password has changed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['password_hash_web' => Hash::make('the-old-hash')])
        ->get(route('profile.edit'))
        ->assertRedirect(route('login'));
});

test('the session that changes the password stays logged in', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('profile.edit'))->assertOk();

    $this->put(route('user-password.update'), [
        'current_password' => 'password',
        'password' => 'brand-new-password',
        'password_confirmation' => 'brand-new-password',
    ])->assertRedirect();

    $this->get(route('profile.edit'))->assertOk();
});

test('deleting an account removes its sessions and reset tokens', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $session = fn (string $id, int $userId) => ['id' => $id, 'user_id' => $userId, 'ip_address' => '192.0.2.1', 'user_agent' => 'Test', 'payload' => '', 'last_activity' => time()];

    DB::table('sessions')->insert([$session('mine', $user->id), $session('theirs', $other->id)]);
    DB::table('password_reset_tokens')->insert([
        ['email' => $user->email, 'token' => 'x', 'created_at' => now()],
        ['email' => $other->email, 'token' => 'y', 'created_at' => now()],
    ]);

    app(DeleteUser::class)->handle($user);

    expect(DB::table('sessions')->pluck('id')->all())->toBe(['theirs'])
        ->and(DB::table('password_reset_tokens')->pluck('email')->all())->toBe([$other->email]);
});

test('deleting an account ends the session and replaces the CSRF token', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->withSession(['marker' => 'kept', '_token' => 'old-token']);

    $this->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertSessionMissing('marker');

    expect(session()->token())->not->toBe('old-token');
});

test('the last admin cannot delete the account', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertSessionHasErrors(['password' => 'Das letzte Admin-Konto kann nicht gelöscht werden.']);

    expect($admin->fresh())->not->toBeNull();
});

test('an admin can leave when another admin remains', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->admin()->create();

    $this->actingAs($admin)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertSessionHasNoErrors();

    expect($admin->fresh())->toBeNull();
});

test('passwords longer than bcrypt can read are refused', function (string $password, bool $allowed) {
    $response = $this->post(route('register.store'), [
        'name' => 'Someone',
        'email' => 'someone@example.com',
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $allowed
        ? $response->assertSessionHasNoErrors()
        : $response->assertSessionHasErrors(['password' => 'Das Passwort ist zu lang.']);

    expect(User::query()->where('email', 'someone@example.com')->exists())->toBe($allowed);
})->with([
    '72 bytes' => [str_repeat('a', 72), true],
    '73 bytes' => [str_repeat('a', 73), false],
    '75 bytes in 25 characters' => [str_repeat('€', 25), false],
]);

test('a page behind the last one responds with 404, and an empty list still works', function () {
    $product = Product::factory()->create();

    $this->get(route('products.show', [$product, 'page' => 1]))->assertOk();
    $this->get(route('products.show', [$product, 'page' => 2]))->assertNotFound();
});

test('a non-numeric id in the search is explained in German', function () {
    $this->actingAs(User::factory()->create())
        ->getJson(route('feature-requests.similar', [Product::factory()->create(), 'title' => 'Mahnung', 'except' => 'abc']))
        ->assertUnprocessable()
        ->assertJsonPath('errors.except.0', 'Except muss eine ganze Zahl sein.');
});
