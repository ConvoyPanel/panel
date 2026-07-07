<?php

namespace App\Http\Middleware;

use App\Models\PersonalAccessToken;
use App\Support\Api\TokenAbilities;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Enforces an API token's scoped abilities on the Application API. A request needs the ability for
 * its resource + action (see {@see TokenAbilities}); a token lacking it is rejected with 403.
 *
 * Web-session requests carry no access token, so they are not ability-scoped — this only bites
 * Sanctum-token callers on /api/application.
 */
class EnforceTokenAbilities
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $required = TokenAbilities::requiredFor($request);

            if (! TokenAbilities::grants($token->abilities ?? [], $required)) {
                throw new AccessDeniedHttpException("This token is missing the required ability: {$required}.");
            }
        }

        return $next($request);
    }
}
