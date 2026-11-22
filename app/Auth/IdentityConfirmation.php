<?php

namespace App\Auth;

use App\Models\PersonalAccessToken;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;

/**
 * The one definition of "identity is confirmed", shared by the middleware that
 * enforces it and the endpoint that reports it.
 *
 * The frontend used to keep its own copy of this window in a zustand store and
 * decide for itself whether the gate should be up. Two clocks for one fact: the
 * browser's copy was dropped on reload (so it re-prompted while the server still
 * trusted the session) and could not notice the window lapsing without a
 * re-render. The server owns it; the client asks.
 */
class IdentityConfirmation
{
    public const SESSION_KEY = 'auth.identity_confirmed_at';

    /** How long a confirmation is good for, in seconds. */
    public const WINDOW = 300;

    public static function confirm(Session $session): void
    {
        $session->put(self::SESSION_KEY, now()->timestamp);
    }

    public static function isConfirmed(Session $session): bool
    {
        return self::expiresIn($session) > 0;
    }

    /**
     * Whether a request clears the gate on an irreversible act (a rebuild, a root password).
     *
     * The gate exists so that a live cookie alone, from an unattended browser or a stolen
     * session, cannot do these things. An API token is not that: minting one already took a
     * confirmed identity, and it is a credential somebody chose to hand to a program. A program
     * has no way to answer a password prompt, so asking it to would make the endpoint
     * unreachable rather than safer. What the token may touch is bounded by its abilities.
     */
    public static function satisfiedBy(Request $request): bool
    {
        if ($request->user()?->currentAccessToken() instanceof PersonalAccessToken) {
            return true;
        }

        return $request->hasSession() && self::isConfirmed($request->session());
    }

    /** Seconds left on the current confirmation; 0 when there is none. */
    public static function expiresIn(Session $session): int
    {
        $confirmedAt = $session->get(self::SESSION_KEY);

        if (! is_int($confirmedAt)) {
            return 0;
        }

        return max(0, $confirmedAt + self::WINDOW - now()->timestamp);
    }
}
