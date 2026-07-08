<?php

namespace App\Http\Middleware;

use App\Models\PersonalAccessToken;
use App\Support\Api\ScopedTokenAbilities;
use App\Support\Api\TokenAbilities;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Enforces an API token's scoped abilities against a resource vocabulary. A request needs the
 * ability for its resource + action (see {@see ScopedTokenAbilities}); a token lacking it is
 * rejected with 403.
 *
 * The vocabulary is passed as a middleware parameter so the same guard serves both the application
 * API (`TokenAbilities`, the default) and the client API (`AccountTokenAbilities`).
 *
 * Web-session requests carry no access token, so they are not ability-scoped — this only bites
 * Sanctum-token callers.
 */
class EnforceTokenAbilities
{
    /**
     * @param  class-string<ScopedTokenAbilities>  $vocabulary
     */
    public function handle(Request $request, Closure $next, string $vocabulary = TokenAbilities::class): Response
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $required = $vocabulary::requiredFor($request);

            if (! $vocabulary::grants($token->abilities ?? [], $required)) {
                throw new AccessDeniedHttpException("This token is missing the required ability: {$required}.");
            }
        }

        return $next($request);
    }
}
