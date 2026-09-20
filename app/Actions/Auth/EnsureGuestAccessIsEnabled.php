<?php

namespace App\Actions\Auth;

use App\Auth\GuestAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

/**
 * Refuses a password login for a guest account while guest access is switched off.
 *
 * Runs after the credentials have been proven, so the message reveals nothing about which
 * addresses have accounts. The session is torn down rather than left half-established.
 */
class EnsureGuestAccessIsEnabled
{
    public function handle(Request $request, Closure $next)
    {
        if (! GuestAccess::permits($request->user())) {
            Auth::guard()->logout();

            throw ValidationException::withMessages([
                Fortify::username() => [GuestAccess::message()],
            ]);
        }

        return $next($request);
    }
}
