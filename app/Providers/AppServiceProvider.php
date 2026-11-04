<?php

declare(strict_types=1);

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();
        $this->configureRoutePatterns();
    }

    /**
     * PostgreSQL throws on an id that is not a bigint, so such a value must not reach the query.
     * Eighteen digits always fit, and the router answers everything else with a 404.
     */
    protected function configureRoutePatterns(): void
    {
        Route::pattern('featureRequest', '[0-9]{1,18}');
    }

    /**
     * Limit the actions that write often or run a search on every pause while typing.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('votes', fn (Request $request) => Limit::perMinute(30)->by((string) $request->user()?->id));
        RateLimiter::for('search', fn (Request $request) => Limit::perMinute(60)->by((string) $request->user()?->id));
        RateLimiter::for('submissions', fn (Request $request) => Limit::perMinute(10)->by((string) $request->user()?->id));
        RateLimiter::for('password-resets', fn (Request $request) => Limit::perMinute(6)->by((string) $request->ip()));
        RateLimiter::for('registrations', fn (Request $request) => Limit::perHour(10)->by((string) $request->ip()));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Model::preventLazyLoading(! app()->isProduction());

        JsonResource::withoutWrapping();

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
