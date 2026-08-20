<?php

namespace App\Http\Controllers\Client;

use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use Illuminate\Http\Request;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Contracts\RecoveryCodesGeneratedResponse;
use Laravel\Fortify\Fortify;

/**
 * Recovery codes are account-level, not authenticator-level: they are minted
 * with whichever second factor comes first (authenticator or passkey), survive
 * swapping one factor for another, and are cleared with the last one. They live
 * under /account/recovery-codes rather than /account/authenticator/* for that
 * reason — the old URL implied a TOTP-only fallback that the column has not
 * described since passkeys started issuing codes too.
 */
class RecoveryCodeController
{
    /**
     * Ungated: the security page decides whether to offer recovery codes at all
     * before identity is confirmed, and gating this read would gate the page.
     *
     * Their presence is the account-level "a second factor exists" signal —
     * enabling either factor mints them, removing the last one clears them — so
     * this needs no separate passkey/authenticator lookup.
     */
    public function status(Request $request)
    {
        return response()->json([
            'enabled' => filled($request->user()->two_factor_recovery_codes),
        ]);
    }

    public function index(Request $request)
    {
        if (! $request->user()->two_factor_recovery_codes) {
            return [];
        }

        return response()->json(json_decode(Fortify::currentEncrypter()->decrypt(
            $request->user()->two_factor_recovery_codes,
        ), true));
    }

    public function store(Request $request, GenerateNewRecoveryCodes $generate)
    {
        $generate($request->user());

        Audit::record(AuditEvent::ACCOUNT_RECOVERY_CODES_REGENERATED, subject: $request->user());

        return app(RecoveryCodesGeneratedResponse::class);
    }
}
