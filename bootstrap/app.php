<?php

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders()
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectUsersTo(AppServiceProvider::HOME);

        $middleware->append([
            \Convoy\Http\Middleware\TrustProxies::class,
            \Convoy\Http\Middleware\PreventRequestsDuringMaintenance::class,
            \Convoy\Http\Middleware\TrimStrings::class,
        ]);

        $middleware->web([
            \Convoy\Http\Middleware\EncryptCookies::class,
            \Convoy\Http\Middleware\VerifyCsrfToken::class,
        ]);

        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'guest' => \Convoy\Http\Middleware\RedirectIfAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
