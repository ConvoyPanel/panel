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
        // pull, not get: a challenge is single use. Leaving it in the session
        // kept it valid indefinitely, so a captured assertion stayed replayable
        // against the same session — the one thing the challenge exists to stop.
        // The is_string guard covers a verify with no create before it;
        // execute() types the options non-nullable, so null was a TypeError and
        // a 500 rather than a rejected attempt.
        $options = $request->session()->pull('passkeys.authentication-options');

        if (! is_string($options)) {
            throw new InvalidPasskeyException;
        }

        $passkey = $this->findPasskeyAction->execute($request->getContent(), $options);

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
