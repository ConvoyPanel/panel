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
            // Fortify is what the login challenge consults. Checking the column
            // directly reported an abandoned setup as enabled, disagreeing with
            // the thing that actually gates login.
            'enabled' => $request->user()->hasEnabledTwoFactorAuthentication(),
        ]);
    }
}
