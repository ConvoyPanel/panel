<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Auth\DisableAuthenticator;
use App\Data\User\ApiKeyData;
use App\Data\User\OAuthConnectionData;
use App\Data\User\PasskeyData;
use App\Data\User\SSHKeyData;
use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Http\Middleware\RequireIdentityConfirmation;
use App\Listeners\AuditAuthenticationSubscriber;
use App\Models\OAuthConnection;
use App\Models\Passkey;
use App\Models\PersonalAccessToken;
use App\Models\SSHKey;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\LaravelData\DataCollection;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Everything that can be used to sign in as one account, from the admin's side.
 *
 * Read-and-revoke only. Nothing here mints a credential on someone else's behalf: an admin who
 * could add an SSH key or an API token to another account would be able to take it over silently,
 * which is a different power from being able to take access away.
 *
 * The routes are scope-bound to `{user}`, so a key belonging to a different account resolves to a
 * 404 before any of this runs — the same answer probing a stranger's key id gets on the client
 * side, and the reason none of these methods re-check ownership.
 *
 * Audited as the existing `account.*` events rather than a parallel `admin.user.*` set. The actor
 * on an audit row is whoever is authenticated and the subject is the account acted on, so an admin
 * revoking someone's passkey already records as two different people — see
 * {@see AuditAuthenticationSubscriber::recordTwoFactor()}, which says so. A second
 * catalog of admin-flavoured duplicates would only split one account's security history in two.
 */
class UserCredentialController
{
    public function __construct(
        private DisableAuthenticator $disableAuthenticator,
    ) {}

    public function apiKeys(User $user)
    {
        return ApiKeyData::collect(
            $user->apiKeys()->latest('id')->get(),
            DataCollection::class,
        );
    }

    public function destroyApiKey(Request $request, User $user, PersonalAccessToken $apiKey)
    {
        $this->denySelf($request, $user);

        $name = $apiKey->name;

        $apiKey->delete();

        Audit::record(
            AuditEvent::ACCOUNT_API_KEY_DELETED,
            subject: $user,
            properties: ['name' => $name],
        );

        return response()->noContent();
    }

    public function sshKeys(User $user)
    {
        return SSHKeyData::collect(
            $user->sshKeys()->latest('id')->get(),
            DataCollection::class,
        );
    }

    public function destroySshKey(Request $request, User $user, SSHKey $sshKey)
    {
        $this->denySelf($request, $user);

        $name = $sshKey->name;

        $sshKey->delete();

        Audit::record(
            AuditEvent::ACCOUNT_SSH_KEY_DELETED,
            subject: $user,
            properties: ['name' => $name],
        );

        return response()->noContent();
    }

    public function passkeys(User $user)
    {
        return PasskeyData::collect(
            $user->passkeys()->latest('id')->get(),
            DataCollection::class,
        );
    }

    public function destroyPasskey(Request $request, User $user, Passkey $passkey)
    {
        $this->denySelf($request, $user);

        $name = $passkey->name;

        $passkey->delete();

        Audit::record(
            AuditEvent::ACCOUNT_PASSKEY_DELETED,
            subject: $user,
            properties: ['name' => $name],
        );

        return response()->noContent();
    }

    public function oauthConnections(User $user)
    {
        return OAuthConnectionData::collect(
            $user->oauthConnections()->latest('created_at')->get(),
            DataCollection::class,
        );
    }

    public function destroyOauthConnection(
        Request $request,
        User $user,
        OAuthConnection $oauthConnection,
    ) {
        $this->denySelf($request, $user);

        $provider = $oauthConnection->provider;

        $oauthConnection->delete();

        Audit::record(
            AuditEvent::ACCOUNT_OAUTH_CONNECTION_DELETED,
            subject: $user,
            properties: ['provider' => $provider],
        );

        return response()->noContent();
    }

    /**
     * Turn off the account's authenticator app — the lockout-recovery path, for the person who
     * lost the phone and cannot get far enough into the panel to fix it themselves.
     *
     * Reuses {@see DisableAuthenticator} rather than clearing the columns here, so an account that
     * still has a passkey keeps its recovery codes, and so the audit entry comes from the same
     * Fortify event every other two-factor change does.
     */
    public function destroyTwoFactor(Request $request, User $user)
    {
        $this->denySelf($request, $user);

        ($this->disableAuthenticator)($user);

        return response()->noContent();
    }

    /**
     * An admin's own credentials are managed from `/security`, never from here.
     *
     * The client-side routes for all of this sit behind {@see RequireIdentityConfirmation},
     * which makes an unattended session or a stolen cookie insufficient to tear down the account's
     * own second factor. This surface has no such gate — it is not the account's own session that
     * authorises the change — so letting it point at the caller would be a way around that check
     * rather than a convenience.
     */
    private function denySelf(Request $request, User $user): void
    {
        if ($user->is($request->user())) {
            throw new BadRequestHttpException(
                'Manage your own credentials from your account security page.',
            );
        }
    }
}
