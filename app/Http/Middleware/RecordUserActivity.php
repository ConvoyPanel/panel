<?php

namespace Convoy\Http\Middleware;

use Closure;
use Convoy\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stamps the account's `last_active_at`, which the telemetry report's
 * active-account counts read (docs/telemetry.md).
 *
 * Only signed-in browser sessions count: a script calling the API with a token
 * is not someone using the panel. At most one write a minute per account, so
 * the SPA's polling doesn't turn into a write per request.
 */
class RecordUserActivity
{
    private const THROTTLE_SECONDS = 60;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();

        if (! $user instanceof User || $request->bearerToken() !== null || ! $request->hasSession()) {
            return $response;
        }

        $now = Carbon::now();

        if ($user->last_active_at?->gt($now->copy()->subSeconds(self::THROTTLE_SECONDS))) {
            return $response;
        }

        // Through the base query builder so `updated_at` keeps meaning "the account was edited".
        // The condition repeats the throttle for a second tab racing this one.
        User::query()
            ->whereKey($user->getKey())
            ->where(fn ($query) => $query
                ->whereNull('last_active_at')
                ->orWhere('last_active_at', '<=', $now->copy()->subSeconds(self::THROTTLE_SECONDS)))
            ->toBase()
            ->update(['last_active_at' => $now]);

        return $response;
    }
}
