<?php

declare(strict_types=1);

use App\Models\FeatureRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Every route the app serves. The list comes from the router, so a new route is attacked automatically.
 *
 * @return list<RoutingRoute>
 */
function attackableRoutes(): array
{
    return collect(Route::getRoutes()->getRoutes())
        ->reject(fn (RoutingRoute $route) => in_array($route->uri(), ['up', '{fallbackPlaceholder}'], true))
        ->reject(fn (RoutingRoute $route) => str_starts_with($route->uri(), 'storage/'))
        // Logging out would end the session of the attacking account.
        ->reject(fn (RoutingRoute $route) => $route->getName() === 'logout')
        ->values()
        ->all();
}

/**
 * Every input name the app reads, taken from its form requests and its source code.
 *
 * @return list<string>
 */
function knownInputNames(): array
{
    $fromSource = collect(File::allFiles(app_path()))
        ->flatMap(function (SplFileInfo $file) {
            preg_match_all('/(?:->(?:enum|query|input|string|integer|boolean|float|date|has|filled)\(|textFilter\(\$request,)\s*\'(\w+)\'/', $file->getContents(), $reads);
            preg_match_all('/\'(\w+)\'\s*=>\s*(?:\[|\$this->\w+Rules\()/', $file->getContents(), $rules);

            return [...$reads[1], ...$rules[1]];
        });

    $fromRequests = collect(File::allFiles(app_path('Http/Requests')))
        ->map(fn (SplFileInfo $file) => 'App\\Http\\Requests\\'.str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname()))
        ->filter(fn (string $class) => is_subclass_of($class, FormRequest::class))
        ->flatMap(fn (string $class) => array_keys((new $class)->rules()));

    // Names read by Fortify and the paginator inside vendor/.
    $fromFramework = ['email', 'password', 'password_confirmation', 'token', 'remember', 'page'];

    return $fromSource->merge($fromRequests)->merge($fromFramework)->unique()->sort()->values()->all();
}

/**
 * @return array<string, mixed>
 */
function hostileValues(): array
{
    return [
        'array' => ['x'],
        'nested array' => [['x']],
        'NUL byte' => "a\0b",
        'invalid UTF-8' => "a\xFFb",
        'not a number' => 'abc',
        'decimal' => '1.5',
        'negative' => '-1',
        'overflow' => '99999999999999999999',
        'largest bigint' => '9223372036854775807',
        'overflow after multiplying' => '2000000000000000000',
        'very long' => str_repeat('a', 10000),
        'wildcards' => '%_%',
        'markup' => '<script>alert(1)</script>',
    ];
}

/**
 * @return array{0: string, 1: string, 2: array<string, mixed>}
 */
function requestFor(RoutingRoute $route, array $bindings, array $input): array
{
    $method = collect($route->methods())->first(fn (string $method) => $method !== 'HEAD');
    $uri = preg_replace_callback('/\{(\w+)\??\}/', fn (array $match) => rawurlencode((string) ($bindings[$match[1]] ?? 'abc')), $route->uri());

    return [$method, '/'.$uri, $input];
}

/**
 * Send one request in its own rolled back transaction, so one failure cannot hide the next.
 *
 * @return list<string>
 */
function serverErrorsFor(array $request, string $label): array
{
    [$method, $uri, $input] = $request;

    DB::beginTransaction();

    $status = test()->call($method, $uri, $input)->getStatusCode();

    DB::rollBack();

    return $status >= 500 ? ["{$status} {$method} {$uri} {$label}"] : [];
}

beforeEach(function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    Notification::fake();

    $this->admin = User::factory()->admin()->create();
    $this->product = Product::factory()->create();
    $this->featureRequest = FeatureRequest::factory()->for($this->product)->for($this->admin, 'author')->create();
    $this->bindings = ['product' => $this->product->slug, 'featureRequest' => $this->featureRequest->id, 'token' => 'abc'];
});

test('the attack covers the routes and input names that exist', function () {
    expect(count(attackableRoutes()))->toBeGreaterThan(20)
        ->and(knownInputNames())->toContain('status', 'sort', 'title', 'description', 'except', 'email', 'password');
});

test('no input name crashes any route with an array', function (bool $asAdmin) {
    if ($asAdmin) {
        $this->actingAs($this->admin);
    }

    $errors = [];

    foreach (attackableRoutes() as $route) {
        foreach (knownInputNames() as $name) {
            $errors = [...$errors, ...serverErrorsFor(requestFor($route, $this->bindings, [$name => ['x']]), "{$name}[]")];
        }
    }

    expect($errors)->toBe([]);
})->with(['guest' => false, 'admin' => true]);

test('no hostile value crashes any route', function (bool $asAdmin) {
    if ($asAdmin) {
        $this->actingAs($this->admin);
    }

    $errors = [];

    foreach (attackableRoutes() as $route) {
        foreach (hostileValues() as $label => $value) {
            $input = array_fill_keys(knownInputNames(), $value);
            $errors = [...$errors, ...serverErrorsFor(requestFor($route, $this->bindings, $input), $label)];
        }
    }

    expect($errors)->toBe([]);
})->with(['guest' => false, 'admin' => true]);

test('no hostile value in the path crashes any route', function (bool $asAdmin) {
    if ($asAdmin) {
        $this->actingAs($this->admin);
    }

    $errors = [];

    foreach (attackableRoutes() as $route) {
        foreach ($route->parameterNames() as $parameter) {
            foreach (hostileValues() as $label => $value) {
                if (is_string($value)) {
                    $bindings = [$parameter => $value] + $this->bindings;
                    $errors = [...$errors, ...serverErrorsFor(requestFor($route, $bindings, []), "{{$parameter}} {$label}")];
                }
            }
        }
    }

    expect($errors)->toBe([]);
})->with(['guest' => false, 'admin' => true]);

test('no hostile cookie crashes any route', function () {
    $this->actingAs($this->admin);

    $errors = [];

    foreach (attackableRoutes() as $route) {
        foreach (['appearance', config('session.cookie'), 'XSRF-TOKEN'] as $cookie) {
            foreach (hostileValues() as $label => $value) {
                [$method, $uri] = requestFor($route, $this->bindings, []);

                DB::beginTransaction();
                $status = $this->call($method, $uri, [], [$cookie => $value])->getStatusCode();
                DB::rollBack();

                if ($status >= 500) {
                    $errors[] = "{$status} {$method} {$uri} cookie {$cookie} {$label}";
                }
            }
        }
    }

    expect($errors)->toBe([]);
});

test('an unknown appearance falls back to the system setting', function (mixed $appearance) {
    $this->call('GET', '/', [], ['appearance' => $appearance])
        ->assertOk()
        ->assertSee("const appearance = 'system';", false);
})->with([['x'], ['nonsense'], ['\\'], ["'"]]);

test('a valid appearance is still used', function () {
    $this->call('GET', '/', [], ['appearance' => 'dark'])
        ->assertOk()
        ->assertSee("const appearance = 'dark';", false);
});

test('a page number that cannot exist shows the first page', function (string $page) {
    $this->get("/products/{$this->product->slug}?page={$page}")
        ->assertOk()
        ->assertInertia(fn ($inertia) => $inertia->where('featureRequests.meta.current_page', 1));
})->with(['2000000000000000000', '9223372036854775807', '99999999999999999999', 'abc', '-1', '0']);

test('a valid page number is still used', function () {
    FeatureRequest::factory()->count(6)->for($this->product)->create();

    $this->get("/products/{$this->product->slug}?page=2")
        ->assertInertia(fn ($inertia) => $inertia->where('featureRequests.meta.current_page', 2));
});

test('a feature request id that cannot exist answers with 404', function (string $id) {
    $this->get("/products/{$this->product->slug}/requests/{$id}")->assertNotFound();
    $this->actingAs($this->admin)->post("/requests/{$id}/vote")->assertNotFound();
    $this->actingAs($this->admin)->put("/requests/{$id}/status", ['status' => 'planned'])->assertNotFound();
})->with(['abc', '1.5', '-1', '99999999999999999999', '9223372036854775808']);

test('malformed filters fall back to the defaults', function (string $query) {
    $this->get("/products/{$this->product->slug}?{$query}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.status', null)
            ->where('filters.sort', 'votes')
            ->has('featureRequests.data', 1));
})->with(['status[]=done', 'sort[]=votes', 'status[a][b]=done', 'status=nonsense']);

test('text the database cannot store is refused with 400', function (string $title) {
    $this->actingAs($this->admin)
        ->post(route('feature-requests.store', $this->product), ['title' => $title, 'description' => 'A long enough text.'])
        ->assertBadRequest();

    expect(FeatureRequest::query()->count())->toBe(1);
})->with(['NUL byte' => "Title\0here", 'invalid UTF-8' => "Title \xFF here"]);

test('an array as email is refused with 400 instead of crashing Fortify', function (string $route) {
    $this->post(route($route), ['email' => ['x']])->assertBadRequest();
})->with(['register.store', 'login.store', 'password.email', 'password.update']);

test('the routes of the removed password confirmation answer with 404', function () {
    $this->actingAs($this->admin);

    $this->get('/user/confirm-password')->assertNotFound();
    $this->post('/user/confirm-password')->assertNotFound();
    $this->get('/user/confirmed-password-status')->assertNotFound();
});

test('an array as password is a validation error', function () {
    $this->actingAs($this->admin)
        ->delete(route('profile.destroy'), ['password' => ['x']])
        ->assertRedirect()
        ->assertSessionHasErrors('password');

    expect($this->admin->fresh())->not->toBeNull();
});

test('valid text with umlauts and emoji is still accepted', function () {
    $this->actingAs($this->admin)
        ->post(route('feature-requests.store', $this->product), ['title' => 'Größe 🚀 ändern', 'description' => 'Bitte die Größe ändern.'])
        ->assertRedirect();

    expect(FeatureRequest::query()->where('title', 'Größe 🚀 ändern')->exists())->toBeTrue();
});
