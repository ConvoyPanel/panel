<?php

namespace App\Http\Controllers\Client;

use Illuminate\Http\Request;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Contracts\RecoveryCodesGeneratedResponse;
use Laravel\Fortify\Fortify;

class RecoveryCodeController
{
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

        return app(RecoveryCodesGeneratedResponse::class);
    }
}
