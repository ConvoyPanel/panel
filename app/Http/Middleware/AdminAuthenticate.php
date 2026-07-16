<?php

namespace App\Http\Middleware;

use App\Models\SystemActor;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AdminAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * The admin surface is authorized for two kinds of caller:
     *  - a web-session root admin (the panel, /api/admin); and
     *  - a panel-wide application token, which is owned by the {@see SystemActor} rather than a
     *    user (/api/application) — being the system actor *is* the authorization, since these
     *    tokens are only mintable by an admin through the session-gated /tokens endpoint.
     *
     * @throws AccessDeniedHttpException
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Sanctum resolves an application token to its tokenable, which for panel-wide tokens is the
        // SystemActor — not the User that the app's typed user() implies.
        /** @var User|SystemActor|null $actor */
        $actor = $request->user();

        if ($actor instanceof SystemActor) {
            return $next($request);
        }

        if (! $actor || ! $actor->root_admin) {
            throw new AccessDeniedHttpException;
        }

        return $next($request);
    }
}
