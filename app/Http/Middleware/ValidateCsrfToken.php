<?php

namespace App\Http\Middleware;

use App\Models\PersonalAccessToken;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken as Middleware;
use Illuminate\Http\Request;

/**
 * CSRF protection for the web group, extended to exempt genuine Sanctum bearer-token requests.
 *
 * The client API (`/api/client`) is served under the web group so the panel SPA can drive it with
 * its session cookie, but it also accepts end-user personal access tokens (`auth:web,sanctum`). A
 * browser cannot attach an `Authorization` header cross-origin without a CORS preflight, so a
 * request carrying a *valid* bearer token cannot be a forged (CSRF) request — this mirrors why
 * Sanctum's stateless `api` group is CSRF-free.
 *
 * Crucially we only skip when the token actually resolves: a forged request that merely bolts on a
 * bogus `Authorization` header (while riding the victim's session cookie) still hits CSRF, because
 * the attacker has no valid token.
 */
class ValidateCsrfToken extends Middleware
{
    protected function inExceptArray($request): bool
    {
        return $this->hasValidBearerToken($request) || parent::inExceptArray($request);
    }

    private function hasValidBearerToken(Request $request): bool
    {
        $bearer = $request->bearerToken();

        if ($bearer === null) {
            return false;
        }

        // Existence of a real token is enough to rule out a browser-forged request; an expired
        // token is still rejected downstream by the Sanctum guard (401), so skipping CSRF for it
        // is harmless.
        return PersonalAccessToken::findToken($bearer) !== null;
    }
}
