<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // You can bind services here if needed
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ------------------------
        // Rate limiting
        // ------------------------
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('global', function (Request $request) {
            return Limit::perMinute(1000);
        });

        // ------------------------
        // Middleware aliases
        // ------------------------
        // Note: Actual middleware registration in groups is normally done in
        // App\Http\Kernel.php. Here we can only define custom middleware aliases
        // or perform boot-time logic if needed.

        // Example of alias registration if you want to attach via Kernel dynamically:
        if (method_exists($this->app['router'], 'aliasMiddleware')) {
            $router = $this->app['router'];

            $router->aliasMiddleware('role', \App\Http\Middleware\CheckRole::class);
            $router->aliasMiddleware('permission', \App\Http\Middleware\CheckPermission::class);
        }
    }
}
