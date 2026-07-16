<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\GeneratePasskeyAuthenticationOptionsAction;
use App\Exceptions\Http\Auth\InvalidPasskeyException;
use App\Models\Passkey;
use Illuminate\Http\Request;
use Spatie\LaravelPasskeys\Actions\FindPasskeyToAuthenticateAction;

class PasskeyLoginController
{
    public function __construct(
        private GeneratePasskeyAuthenticationOptionsAction $generateOptionsAction,
        private FindPasskeyToAuthenticateAction $findPasskeyAction,
    ) {}

    public function create(Request $request)
    {
        $options = $this->generateOptionsAction->execute();

        $request->session()->put('passkeys.authentication-options', $options);

        return $options;
    }

    public function store(Request $request)
    {
        $passkey = $this->findPasskeyAction->execute(
            $request->getContent(),
            $request->session()->get('passkeys.authentication-options'),
        );

        if (! $passkey) {
            throw new InvalidPasskeyException;
        }

        /** @var Passkey $passkey (config binds passkeys.models.passkey to our subclass) */
        $user = $passkey->user;

        auth()->login($user);

        $request->session()->regenerate();

        return response()->noContent();
    }
}
