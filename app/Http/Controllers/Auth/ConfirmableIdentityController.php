<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\GeneratePasskeyAuthenticationOptionsAction;
use App\Exceptions\Http\Auth\InvalidAuthenticationMethodException;
use App\Exceptions\Http\Auth\InvalidPasskeyException;
use App\Http\Requests\Auth\ConfirmIdentityRequest;
use App\Models\Passkey;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Fortify\Http\Responses\FailedPasswordConfirmationResponse;
use Spatie\LaravelPasskeys\Actions\FindPasskeyToAuthenticateAction;

/**
 * Controller responsible for handling identity reconfirmation
 * for users who are already authenticated. This is typically
 * used for sensitive actions requiring additional verification.
 */
class ConfirmableIdentityController
{
    public function __construct(
        private GeneratePasskeyAuthenticationOptionsAction $generateOptionsAction,
        private FindPasskeyToAuthenticateAction $findPasskeyAction,
    ) {}

    public function generatePasskeyAuthOptions(Request $request)
    {
        $options = $this->generateOptionsAction->execute();

        // Its own key, not the guest login flow's `passkeys.authentication-options`:
        // these are separate ceremonies with separate lifetimes, and a challenge
        // minted to prove presence now must never be satisfiable by one minted
        // to log in.
        $request->session()->put('passkeys.identity-options', $options);

        return $options;
    }

    public function store(ConfirmIdentityRequest $request)
    {
        $user = $request->user();
        if (! $user instanceof User) {
            throw new InvalidAuthenticationMethodException;
        }

        if ($request->filled('passkey')) {
            // pull, not get: identity confirmation exists to prove someone is
            // present *now*, so its challenge is strictly single use. Left in the
            // session it stayed valid, and the same assertion could re-confirm
            // identity after the 5-minute window lapsed — replaying presence is
            // exactly what the gate is meant to prevent.
            $options = $request->session()->pull('passkeys.identity-options');

            if (! is_string($options)) {
                throw new InvalidPasskeyException;
            }

            /** @var Passkey|null $passkey (config binds passkeys.models.passkey to our subclass) */
            $passkey = $this->findPasskeyAction->execute($request->input('passkey'), $options);

            if (! $passkey || $passkey->user->id !== $user->id) {
                throw new InvalidPasskeyException;
            }
        } elseif ($request->filled('password')) {
            // Handle password confirmation
            $confirmed = auth()->validate([
                'email' => $user->email,
                'password' => $request->input('password'),
            ]);

            if (! $confirmed) {
                return app(FailedPasswordConfirmationResponse::class);
            }
        } else {
            throw new InvalidAuthenticationMethodException;
        }

        // Store the confirmation timestamp in the session
        $request->session()->put('auth.identity_confirmed_at', now()->timestamp);

        return response()->noContent();
    }
}
