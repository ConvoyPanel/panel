<?php

namespace Convoy\Providers;

use Convoy\Http\Middleware\AdminAuthenticate;
use Convoy\Models\Server;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     *
     * @return void
     */
    public function boot()
    {
        Route::bind('server', function ($value) {
            return Server::query()->where(strlen($value) === 8 ? 'uuid_short' : 'uuid', $value)->firstOrFail();
        });

        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('web')->group(function () {
                Route::middleware('guest')->group(base_path('routes/auth.php'));

                Route::middleware(['auth.session'])
                    ->group(base_path('routes/base.php'));

                Route::middleware(['auth'])->prefix('/api/client')
                    ->as('client.')
                    ->group(base_path('routes/api-client.php'));

                Route::middleware(['auth', AdminAuthenticate::class])
                    ->prefix('/api/admin')
                    ->as('admin.')
                    ->group(base_path('routes/api-admin.php'));
            });

            Route::middleware(['api'])->group(function () {
                Route::middleware(['auth:sanctum'])
                    ->prefix('/api/application')
                    ->as('application.')
                    ->group(base_path('routes/api-application.php'));
            });
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
