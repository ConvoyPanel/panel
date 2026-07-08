<?php

namespace App\Http\Middleware;

use App\Models\SessionRecord;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records/refreshes the metadata for the current web session so it can be listed and revoked from
 * the account area (Laravel's own session listing needs the database driver; we run on Redis).
 *
 * Only touches authenticated, session-backed requests, and throttles writes to once per minute per
 * session so the SPA's frequent polling doesn't hammer the table.
 */
class RecordSessionActivity
{
    /** Don't rewrite a session row more than once per this many seconds. */
    private const THROTTLE_SECONDS = 60;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();

        // Skip unauthenticated requests and Sanctum bearer-token callers (the client API accepts
        // both; only real web sessions belong in the list). A bearer token is the reliable signal —
        // a session request never carries one.
        if ($user === null || $request->bearerToken() !== null || ! $request->hasSession()) {
            return $response;
        }

        $sessionId = $request->session()->getId();

        $record = SessionRecord::query()->firstOrNew(['session_id' => $sessionId]);

        if (
            $record->exists
            && $record->last_active_at->gt(Carbon::now()->subSeconds(self::THROTTLE_SECONDS))
        ) {
            return $response;
        }

        $record->forceFill([
            'user_id' => $user->getAuthIdentifier(),
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            'last_active_at' => Carbon::now(),
        ])->save();

        return $response;
    }
}
