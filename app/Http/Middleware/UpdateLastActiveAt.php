<?php

namespace Convoy\Http\Middleware;

use Closure;
use Convoy\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records when a signed-in user was last seen, for telemetry's daily, weekly
 * and monthly active-user counts. Pages poll the API every few seconds, so the
 * column is written at most once every few minutes per user.
 */
class UpdateLastActiveAt
{
    private const INTERVAL_MINUTES = 5;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();

        if ($user instanceof User && (
            is_null($user->last_active_at)
            || $user->last_active_at->lt(now()->subMinutes(self::INTERVAL_MINUTES))
        )) {
            // A plain query: no model events or validation, and no change to updated_at.
            User::query()->whereKey($user->getKey())->toBase()->update(['last_active_at' => now()]);
        }

        return $response;
    }
}
