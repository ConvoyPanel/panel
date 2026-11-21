<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\GeneratePasskeyRegisterOptionsAction;
use App\Actions\Auth\StorePasskeyAction;
use App\Enums\Audit\AuditEvent;
use App\Exceptions\Http\Auth\InvalidPasskeyException;
use App\Facades\Audit;
use App\Http\Requests\Auth\AcceptInviteRequest;
use App\Models\UserInvite;
use App\Services\Users\UserInviteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class InviteController
{
    /**
     * Where the registration options wait between the two passkey requests. Not the key the
     * account screen uses: that one belongs to a signed-in session, and this belongs to an invite.
     */
    private const PASSKEY_SESSION_KEY = 'invites.passkey-registration';

    public function __construct(private UserInviteService $invites) {}

    /**
     * What the invite screen needs to address the person by name before they have signed in.
     *
     * Deliberately thin: a name and the address the account will sign in with, nothing about the
     * panel's contents. An unauthenticated caller holding a guessed token learns only what they
     * would already know from the email that carried it.
     */
    public function show(string $token)
    {
        $invite = $this->resolveOrFail($token);

        return response()->json([
            'data' => [
                'name' => $invite->user->name,
                'email' => $invite->user->email,
                'expiresAt' => $invite->expires_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Set the password and sign them in.
     *
     * Signing in immediately is the point of arriving here: the alternative is bouncing someone
     * who has just proved they hold the invite to a login form to retype what they typed a
     * second ago.
     */
    public function store(AcceptInviteRequest $request, string $token)
    {
        $invite = $this->resolveOrFail($token);

        $user = $this->invites->accept($invite, $request->string('password')->toString());

        Auth::login($user);

        $request->session()->regenerate();

        // Recorded against the account itself: this is the moment it becomes usable, and the
        // actor is the account's own owner rather than the admin who created it.
        Audit::record(
            AuditEvent::AUTH_INVITE_ACCEPTED,
            subject: $user,
            properties: ['ip' => $request->ip(), 'method' => 'password'],
        );

        return response()->noContent();
    }

    /**
     * Registration options for a passkey as the account's first credential.
     *
     * The same options the account screen hands out, for the invited account rather than the
     * session's: there is no session account yet, and the token is what says whose this is.
     */
    public function passkeyOptions(
        Request $request,
        GeneratePasskeyRegisterOptionsAction $generateOptions,
        string $token,
    ) {
        $invite = $this->resolveOrFail($token);

        $options = $generateOptions->execute($invite->user);

        // Pinned to the account, so options fetched for one invite cannot be spent on another
        // opened later in the same browser.
        $request->session()->put(self::PASSKEY_SESSION_KEY, [
            'user_id' => $invite->user_id,
            'options' => $options,
        ]);

        return $options;
    }

    /**
     * Store the passkey, burn the invite and sign them in.
     *
     * The first passkey on an account mints its recovery codes, the same as on the account
     * screen, and this is the only time they can be shown.
     */
    public function storePasskey(
        Request $request,
        StorePasskeyAction $storePasskey,
        GenerateNewRecoveryCodes $generateRecoveryCodes,
        string $token,
    ) {
        $invite = $this->resolveOrFail($token);

        // pull, not get: a registration challenge is single use, like the sign-in one.
        $pending = $request->session()->pull(self::PASSKEY_SESSION_KEY);

        if (! is_array($pending)
            || ($pending['user_id'] ?? null) !== $invite->user_id
            || ! is_string($pending['options'] ?? null)) {
            throw new InvalidPasskeyException;
        }

        [$user, $recoveryCodesCreated] = DB::transaction(function () use ($request, $storePasskey, $generateRecoveryCodes, $invite, $pending) {
            $passkey = $storePasskey->execute(
                authenticatable: $invite->user,
                passkeyJson: $request->getContent(),
                passkeyOptionsJson: $pending['options'],
                hostName: $request->getHost(),
            );

            $user = $this->invites->acceptWithPasskey($invite);

            // Always true for an account nobody has signed in to yet. Checked anyway so a
            // re-issued invite on an older account cannot throw away codes it already has.
            $recoveryCodesCreated = empty($user->two_factor_recovery_codes);

            if ($recoveryCodesCreated) {
                $generateRecoveryCodes($user);
            }

            Audit::record(
                AuditEvent::ACCOUNT_PASSKEY_CREATED,
                subject: $user,
                properties: ['name' => $passkey->name, 'recovery_codes_created' => $recoveryCodesCreated],
            );

            return [$user, $recoveryCodesCreated];
        });

        Auth::login($user);

        $request->session()->regenerate();

        Audit::record(
            AuditEvent::AUTH_INVITE_ACCEPTED,
            subject: $user,
            properties: ['ip' => $request->ip(), 'method' => 'passkey'],
        );

        return response()->json([
            'recovery_codes' => $recoveryCodesCreated
                ? json_decode(Fortify::currentEncrypter()->decrypt(
                    $user->fresh()->two_factor_recovery_codes,
                ), true)
                : null,
        ]);
    }

    private function resolveOrFail(string $token): UserInvite
    {
        $invite = $this->invites->resolve($token);

        if ($invite === null) {
            // Unknown, spent and expired are one answer. Telling them apart would confirm that a
            // guessed token once meant something.
            throw new NotFoundHttpException('This invitation is no longer valid.');
        }

        return $invite;
    }
}
