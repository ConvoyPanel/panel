<?php

namespace Convoy\Providers;

use Convoy\Http\Middleware\AdminAuthenticate;
use Convoy\Http\Middleware\AuthorizeProprietaryToken;
use Convoy\Http\Middleware\ForceJsonResponse;
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
        //Route::model('server', Server::class);

        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('web')->group(function () {
                /* Route::middleware([ForceJsonResponse::class, AuthorizeProprietaryToken::class, 'api'])
                    ->prefix('/api/application')
                    ->group(base_path('routes/api-application.php'));
 */
                Route::middleware(['auth.session'])
                    ->group(base_path('routes/base.php'));
            });

            /* Route::middleware('web')
                ->group(base_path('routes/auth.php'));

            Route::middleware(['web', 'auth'])
                ->group(base_path('routes/client.php'));

            Route::middleware(['web', 'auth', AdminAuthenticate::class])
                ->prefix('/admin')
                ->group(base_path('routes/admin.php')); */
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
