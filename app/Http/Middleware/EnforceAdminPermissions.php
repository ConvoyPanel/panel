<?php

namespace App\Http\Middleware;

use App\Models\SystemActor;
use App\Models\User;
use App\Support\Admin\AdminPermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Enforces an admin role's permissions against the request, the mirror of
 * {@see EnforceTokenAbilities}.
 *
 * Runs after {@see AdminAuthenticate}, so the caller is already known to be an admin or the
 * system actor. A request whose resource is not in the catalog requires a permission that only a
 * superadmin holds, which keeps a newly added route closed until it is classified.
 *
 * Application tokens are owned by the {@see SystemActor} rather than by a person, so there is no
 * role to consult; their scope is the token's own abilities, enforced by
 * {@see EnforceTokenAbilities}.
 */
class EnforceAdminPermissions
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|SystemActor|null $actor */
        $actor = $request->user();

        if ($actor instanceof SystemActor) {
            return $next($request);
        }

        $required = AdminPermissions::requiredFor($request);

        if ($required === null) {
            // Nothing in the catalog covers this path. Only an unrestricted role passes.
            if (! $actor?->root_admin) {
                throw new AccessDeniedHttpException('This area is restricted to full administrators.');
            }

            return $next($request);
        }

        if (! $actor?->hasAdminPermission($required)) {
            throw new AccessDeniedHttpException("Your role is missing the required permission: {$required->value}.");
        }

        return $next($request);
    }
}
