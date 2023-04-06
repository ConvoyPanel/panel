<?php

namespace Convoy\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AdminAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     *
     * @throws AccessDeniedHttpException
     */
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user() || ! $request->user()->root_admin) {
            throw new AccessDeniedHttpException();
        }

        return $next($request);
    }
}
