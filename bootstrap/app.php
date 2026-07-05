<?php

use App\Exceptions\HasErrorCode;
use App\Http\Middleware\AdminAuthenticate;
use App\Http\Middleware\Coterm\CotermAuthenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders()
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            Route::middleware('web')->group(function () {
                Route::prefix('/api/auth')
                    ->group(base_path('routes/api-auth.php'));

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
                // The Application API (external Bearer-token clients) shares the
                // exact same route definitions as the admin panel — one source
                // of truth. Session vs. token access is differentiated at the
                // guard (auth:sanctum here vs. web session on /api/admin), and
                // token-forbidden routes opt out via DenyApiTokenAccess.
                Route::middleware(['auth:sanctum', AdminAuthenticate::class])
                    ->prefix('/api/application')
                    ->as('application.')
                    ->scopeBindings()
                    ->group(base_path('routes/api-admin.php'));

                Route::middleware([CotermAuthenticate::class])
                    ->prefix('/api/coterm')
                    ->as('coterm.')
                    ->scopeBindings()
                    ->group(base_path('routes/api-coterm.php'));
            });
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Surface a stable, machine-readable `code` for exceptions that opt in
        // via HasErrorCode. Nothing is auto-derived from the class name, so a
        // fork never leaks an exception it didn't explicitly code.
        $exceptions->render(function (HasErrorCode $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 400;
            $headers = $e instanceof HttpExceptionInterface ? $e->getHeaders() : [];

            return response()->json([
                'message' => $e->getMessage(),
                'code' => $e->errorCode(),
            ], $status, $headers);
        });
    })->create();
