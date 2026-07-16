<?php

namespace App\Http\Middleware;

use App\Auth\IdentityConfirmation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RequireIdentityConfirmation
{
    public function handle(Request $request, Closure $next)
    {
        if (! IdentityConfirmation::isConfirmed($request->session())) {
            throw new AccessDeniedHttpException('Your identity must be confirmed to access this resource.');
        }

        return $next($request);
    }
}
