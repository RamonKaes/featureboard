<?php

declare(strict_types=1);

use App\Models\FeatureRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

test('every route is either public on purpose or requires login', function () {
    $public = [
        'home', 'products.show', 'products.roadmap', 'feature-requests.show',
        'login', 'login.store', 'register', 'register.store',
        'password.request', 'password.email', 'password.reset', 'password.update',
        'storage.local', 'storage.local.upload',
    ];

    $unprotected = collect(Route::getRoutes()->getRoutes())
        ->reject(fn (RoutingRoute $route) => collect($route->gatherMiddleware())->contains(fn (string $middleware) => str_starts_with($middleware, 'auth')))
        ->reject(fn (RoutingRoute $route) => in_array($route->getName(), $public, true))
        ->reject(fn (RoutingRoute $route) => in_array($route->uri(), ['up', '{fallbackPlaceholder}'], true))
        ->map(fn (RoutingRoute $route) => implode('|', $route->methods()).' '.$route->uri())
        ->values();

    expect($unprotected->all())->toBe([]);
});

test('the vote and search routes are rate limited', function (string $name, string $limiter) {
    expect(Route::getRoutes()->getByName($name)?->gatherMiddleware())->toContain("throttle:{$limiter}");
})->with([
    ['votes.store', 'votes'],
    ['votes.destroy', 'votes'],
    ['feature-requests.similar', 'search'],
]);

test('voting is limited to 30 requests per minute per user', function () {
    $user = User::factory()->create();
    $featureRequest = FeatureRequest::factory()->create();

    foreach (range(1, 30) as $attempt) {
        $this->actingAs($user)->post(route('votes.store', $featureRequest))->assertRedirect();
    }

    $this->actingAs($user)->post(route('votes.store', $featureRequest))->assertTooManyRequests();
    $this->actingAs(User::factory()->create())->post(route('votes.store', $featureRequest))->assertRedirect();
});

test('the search is limited to 60 requests per minute per user', function () {
    $user = User::factory()->create();
    $url = route('feature-requests.similar', [Product::factory()->create(), 'title' => 'Mahnung']);

    foreach (range(1, 60) as $attempt) {
        $this->actingAs($user)->getJson($url)->assertOk();
    }

    $this->actingAs($user)->getJson($url)->assertTooManyRequests();
});

test('public pages do not expose email addresses of other users', function () {
    $featureRequest = FeatureRequest::factory()->create();
    $email = $featureRequest->author?->email;

    $this->get(route('feature-requests.show', [$featureRequest->product, $featureRequest]))->assertDontSee((string) $email);
    $this->get(route('products.show', $featureRequest->product))->assertDontSee((string) $email);
});

test('the admin flag cannot be set through the profile form', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Mallory',
        'email' => $user->email,
        'is_admin' => true,
    ]);

    expect($user->fresh()?->is_admin)->toBeFalse();
});

test('the shared user data contains no secrets', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->missing('auth.user.password')
            ->missing('auth.user.remember_token'));
});

test('a foreign host cannot poison the link in the password reset mail', function () {
    $user = User::factory()->create();
    Notification::fake();
    $this->app['env'] = 'production';

    $this->withServerVariables(['HTTP_HOST' => 'evil.example'])
        ->post('http://evil.example/forgot-password', ['email' => $user->email])
        ->assertBadRequest();

    Notification::assertNothingSent();
});

test('the configured host keeps working behind the host check', function () {
    $user = User::factory()->create();
    Notification::fake();
    $this->app['env'] = 'production';

    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->post(config('app.url').'/forgot-password', ['email' => $user->email])->assertRedirect();

    Notification::assertSentTo($user, ResetPassword::class);
});

test('every writing route has a rate limit or is exempt for a stated reason', function () {
    $exempt = [
        'logout' => 'ends the own session',
        'profile.update' => 'changes only the own account',
        'profile.destroy' => 'needs the password and ends the account',
        'feature-requests.status.update' => 'admins only',
    ];

    $unlimited = collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RoutingRoute $route) => array_diff($route->methods(), ['GET', 'HEAD', 'OPTIONS']) !== [])
        ->reject(fn (RoutingRoute $route) => in_array('GET', $route->methods(), true))
        ->reject(fn (RoutingRoute $route) => collect($route->gatherMiddleware())->contains(fn (string $middleware) => str_starts_with($middleware, 'throttle')))
        ->reject(fn (RoutingRoute $route) => array_key_exists((string) $route->getName(), $exempt))
        ->reject(fn (RoutingRoute $route) => str_starts_with($route->uri(), 'storage/') || str_starts_with($route->uri(), 'user/'))
        ->map(fn (RoutingRoute $route) => implode('|', $route->methods()).' '.$route->uri())
        ->values();

    expect($unlimited->all())->toBe([]);
});

test('submitting is limited to 10 requests per minute per user', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    $input = ['title' => 'Ein gültiger Titel', 'description' => 'Eine gültige Beschreibung.'];

    foreach (range(1, 10) as $attempt) {
        $this->actingAs($user)->post(route('feature-requests.store', $product), $input)->assertRedirect();
    }

    $this->actingAs($user)->post(route('feature-requests.store', $product), $input)->assertTooManyRequests();
    $this->actingAs(User::factory()->create())->post(route('feature-requests.store', $product), $input)->assertRedirect();

    expect(FeatureRequest::query()->count())->toBe(11);
});

test('registration is limited to 10 accounts per hour per address', function () {
    foreach (range(1, 10) as $attempt) {
        auth()->logout();

        $this->post(route('register.store'), [
            'name' => "Person {$attempt}",
            'email' => "person{$attempt}@example.com",
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect();
    }

    auth()->logout();

    $this->post(route('register.store'), [
        'name' => 'One Too Many',
        'email' => 'toomany@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertTooManyRequests();

    expect(User::query()->count())->toBe(10);
});
