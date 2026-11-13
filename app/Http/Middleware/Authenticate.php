<?php

namespace Convoy\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // The API answers 401 instead (see the exception handler). The sign-in
        // page is a route of the single-page app, not a named Laravel route:
        // route('login') doesn't exist, and asking for it threw a 500.
        if ($request->expectsJson() || $request->is('api/*')) {
            return null;
        }

        return '/auth/login';
    }
}
