<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthenticatorStatusController extends Controller
{
    public function __invoke(Request $request)
    {
        return response()->json([
            // Defer to Fortify rather than reading `two_factor_secret` here:
            // with confirmation enabled a secret alone is not enabled, and
            // Keep this authenticator-specific even though the account-level
            // second-factor check also accepts a passkey. Checking the secret
            // directly would still report an abandoned setup as enabled.
            'enabled' => $request->user()->hasEnabledTwoFactorAuthentication(),
        ]);
    }
}
