<?php

namespace App\Http\Middleware;

use App\Auth\GuestAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Locks out guest accounts while guest access is switched off.
 *
 * On the API groups rather than at the login form alone, because a session can start in several
 * ways -- password, passkey, OAuth, an SSO link, or a cookie that predates the switch being
 * flipped -- and a kill switch that only covers one of them is not a kill switch.
 *
 * Standard accounts and the system actor never touch the settings read.
 */
class EnforceGuestAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! GuestAccess::permits($request->user())) {
            throw new AccessDeniedHttpException(GuestAccess::message());
        }

        return $next($request);
    }
}
