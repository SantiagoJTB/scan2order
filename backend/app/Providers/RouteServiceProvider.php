<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth-login', function (Request $request) {
            $email = strtolower((string) $request->input('email', 'guest'));
            return Limit::perMinute(8)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('auth-forgot-password', function (Request $request) {
            $email = strtolower((string) $request->input('email', 'guest'));
            return Limit::perMinutes(15, 4)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('auth-reset-password', function (Request $request) {
            return Limit::perMinutes(15, 6)->by($request->ip());
        });

        RateLimiter::for('auth-mfa-setup', function (Request $request) {
            return Limit::perMinutes(10, 5)->by((string) $request->user()?->id.'|'.$request->ip());
        });

        RateLimiter::for('auth-mfa-verify', function (Request $request) {
            return Limit::perMinutes(10, 10)->by((string) $request->user()?->id.'|'.$request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
