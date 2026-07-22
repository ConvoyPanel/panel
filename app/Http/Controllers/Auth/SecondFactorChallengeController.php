<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\GeneratePasskeyAuthenticationOptionsAction;
use App\Exceptions\Http\Auth\InvalidPasskeyException;
use App\Models\Passkey;
use App\Models\User;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Spatie\LaravelPasskeys\Actions\FindPasskeyToAuthenticateAction;

class SecondFactorChallengeController
{
    public function __construct(
        private StatefulGuard $guard,
        private GeneratePasskeyAuthenticationOptionsAction $generateOptionsAction,
        private FindPasskeyToAuthenticateAction $findPasskeyAction,
    ) {}

    public function show(Request $request)
    {
        $user = $this->challengedUser($request);

        return response()->json([
            'authenticator' => $user->hasEnabledTwoFactorAuthentication(),
            'passkey' => $user->passkeys()->exists(),
            'recovery' => filled($user->two_factor_recovery_codes),
        ]);
    }

    public function create(Request $request)
    {
        $user = $this->challengedUser($request);

        if (! $user->passkeys()->exists()) {
            throw new InvalidPasskeyException;
        }

        $options = $this->generateOptionsAction->execute();

        $request->session()->put('passkeys.second-factor-options', $options);

        return $options;
    }

    public function store(Request $request)
    {
        $user = $this->challengedUser($request);
        $options = $request->session()->pull('passkeys.second-factor-options');

        if (! is_string($options)) {
            throw new InvalidPasskeyException;
        }

        /** @var Passkey|null $passkey Config binds the package action to our model subclass. */
        $passkey = $this->findPasskeyAction->execute($request->getContent(), $options);

        // The challenge is for the password-validated login.id, not whichever
        // account happens to own the asserted credential.
        if (! $passkey || ! $passkey->user->is($user)) {
            throw new InvalidPasskeyException;
        }

        $remember = $request->session()->pull('login.remember', false);
        $request->session()->forget('login.id');

        $this->guard->login($user, $remember);
        $request->session()->regenerate();

        return response()->noContent();
    }

    private function challengedUser(Request $request): User
    {
        $id = $request->session()->get('login.id');
        $user = $id ? $this->guard->getProvider()->retrieveById($id) : null;

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
