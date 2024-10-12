<?php

namespace App\Providers;

use App\Http\Middleware\AdminAuthenticate;
use App\Http\Middleware\Coterm\CotermAuthenticate;
use App\Models\Server;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
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
     */
    public function boot(): void
    {
        Route::bind('server', function ($value) {
            return Server::query()->where(strlen($value) === 8 ? 'uuid_short' : 'uuid', $value)
                         ->firstOrFail();
        });

        $this->routes(function () {
            Route::middleware('web')->group(function () {
                Route::middleware('guest')->group(base_path('routes/auth.php'));

                Route::middleware(['auth.session'])
                     ->group(base_path('routes/base.php'));

                Route::middleware(['auth'])->prefix('/api/client')
                     ->as('client.')
                     ->scopeBindings()
                     ->group(base_path('routes/api-client.php'));

                Route::middleware(['auth', AdminAuthenticate::class])
                     ->prefix('/api/admin')
                     ->as('admin.')
                     ->scopeBindings()
                     ->group(base_path('routes/api-admin.php'));
            });

            Route::middleware(['api'])->group(function () {
                Route::middleware(['auth:sanctum'])
                     ->prefix('/api/application')
                     ->as('application.')
                     ->scopeBindings()
                     ->group(base_path('routes/api-application.php'));

                Route::middleware([CotermAuthenticate::class])
                     ->prefix('/api/coterm')
                     ->as('coterm.')
                     ->scopeBindings()
                     ->group(base_path('routes/api-coterm.php'));
            });
        });
    }
}
