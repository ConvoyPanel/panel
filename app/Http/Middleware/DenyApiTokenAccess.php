<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Blocks routes that must never be driven by an Application API token, even
 * though the rest of the admin surface is shared with the token API.
 *
 * The admin route file is served under two guards: the panel's web session
 * (/api/admin) and Sanctum Bearer tokens (/api/application). A web-session
 * request carries no Sanctum access token, so currentAccessToken() is null; a
 * real Application token resolves to a PersonalAccessToken. So "session only"
 * is exactly "there is no access token" — e.g. an API token must not be able
 * to mint or revoke other tokens.
 *
 * NOTE: if the panel is ever moved onto Sanctum's stateful guard, a session
 * request would instead carry a TransientToken (non-null) — this check would
 * then need to exclude TransientToken to keep letting the panel manage tokens.
 */
class DenyApiTokenAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->currentAccessToken() !== null) {
            throw new AccessDeniedHttpException('This endpoint cannot be accessed with an API token.');
        }

        return $next($request);
    }
}
