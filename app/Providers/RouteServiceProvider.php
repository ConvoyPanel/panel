<?php

namespace Convoy\Providers;

use Convoy\Http\Middleware\AdminAuthenticate;
use Convoy\Http\Middleware\Coterm\CotermAuthenticate;
use Convoy\Models\Server;
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
        // These models bind by their bigint id. MySQL answered /nodes/abc with "no such
        // row" and a 404; Postgres refuses to compare the id with text, which was a
        // 500. A value that can't be an id now doesn't match the route, so it's a 404
        // without a query.
        foreach (['node', 'user', 'address_pool', 'address', 'coterm', 'location'] as $parameter) {
            Route::pattern($parameter, '[0-9]{1,18}');
        }

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
                Route::middleware(['auth:sanctum', AdminAuthenticate::class])
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
