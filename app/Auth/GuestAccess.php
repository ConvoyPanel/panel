<?php

namespace App\Auth;

use App\Actions\Auth\EnsureGuestAccessIsEnabled;
use App\Http\Middleware\EnforceGuestAccess;
use App\Models\User;
use App\Settings\PermissionSettings;

/**
 * Whether a guest account may be used right now.
 *
 * `PermissionSettings::$allow_guest_accounts` is a kill switch, not a creation flag: an operator
 * who turns it off wants the door closed on the guests already through, not merely on the next
 * one. Their shares are kept, so turning it back on restores exactly what was there.
 *
 * Consulted in two places, on purpose. {@see EnsureGuestAccessIsEnabled} is what
 * gives a clear answer at the login form; {@see EnforceGuestAccess} is what
 * makes the switch true for every other way into a session -- a passkey, an OAuth redirect, an SSO
 * link, a browser cookie that predates the switch being flipped.
 */
final class GuestAccess
{
    public static function permits(?object $user): bool
    {
        if (! $user instanceof User || ! $user->isGuest()) {
            return true;
        }

        return app(PermissionSettings::class)->allow_guest_accounts;
    }

    public static function message(): string
    {
        return 'This account cannot sign in. Contact the person who shared a server with you.';
    }
}
