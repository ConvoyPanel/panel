<?php

namespace App\Services\Auth;

use App\Exceptions\Http\Auth\OAuthAccountNotProvisionedException;
use App\Exceptions\Http\Auth\OAuthIdentityAlreadyLinkedException;
use App\Exceptions\Http\Auth\OAuthProviderNotEnabledException;
use App\Models\OAuthConnection;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class OAuthAuthenticationService
{
    /**
     * The Socialite drivers whose returned email is only ever a verified, primary
     * address (so an explicit `email_verified` claim isn't sent). For every other
     * provider we require an explicit truthy `email_verified` in the raw payload
     * before we'll auto-link/provision by email.
     */
    private const IMPLICITLY_VERIFIED_EMAIL_PROVIDERS = ['github', 'gitlab'];

    /**
     * The providers that are both listed in config/oauth.php as enabled AND have a
     * client id configured in config/services.php, keyed by driver with their label.
     *
     * @return array<string, string>
     */
    public function enabledProviders(): array
    {
        $providers = [];

        foreach ((array) config('oauth.providers', []) as $driver => $meta) {
            if ($this->isEnabled($driver)) {
                $providers[$driver] = (string) ($meta['label'] ?? Str::title($driver));
            }
        }

        return $providers;
    }

    /**
     * Assert the driver is a configured, enabled provider, or throw. Callers use this
     * to gate both the redirect and callback endpoints so a disabled/unknown provider
     * never reaches Socialite.
     */
    public function ensureEnabled(string $provider): void
    {
        if (! $this->isEnabled($provider)) {
            throw new OAuthProviderNotEnabledException($provider);
        }
    }

    private function isEnabled(string $provider): bool
    {
        return (bool) config("oauth.providers.{$provider}.enabled")
            && filled(config("services.{$provider}.client_id"));
    }

    /**
     * Resolve the Convoy user a federated sign-in should authenticate as, applying the
     * configured link/registration policy:
     *   1. an existing connection for this provider identity wins outright;
     *   2. otherwise, when enabled, link to an existing user by *verified* email;
     *   3. otherwise, when registration is enabled, provision a new non-admin user;
     *   4. otherwise refuse (the door is closed).
     */
    public function resolveForLogin(string $provider, SocialiteUser $socialiteUser): User
    {
        $connection = $this->findConnection($provider, $socialiteUser);

        if ($connection instanceof OAuthConnection) {
            $this->touchConnection($connection, $socialiteUser);

            /** @var User $user */
            $user = $connection->user;

            return $user;
        }

        if (config('oauth.link_by_verified_email') && $this->emailIsVerified($provider, $socialiteUser)) {
            $user = User::query()->where('email', '=', $socialiteUser->getEmail())->first();

            if ($user instanceof User) {
                $this->createConnection($user, $provider, $socialiteUser);

                return $user;
            }
        }

        if (config('oauth.registration')) {
            return $this->provisionUser($provider, $socialiteUser);
        }

        throw new OAuthAccountNotProvisionedException;
    }

    /**
     * Link a provider identity to an already-authenticated user (the account-settings
     * "Connect" flow). Idempotent for the same user; conflicts if the identity is owned
     * by someone else.
     */
    public function linkToUser(User $user, string $provider, SocialiteUser $socialiteUser): OAuthConnection
    {
        $connection = $this->findConnection($provider, $socialiteUser);

        if ($connection instanceof OAuthConnection) {
            if ($connection->user_id !== $user->id) {
                throw new OAuthIdentityAlreadyLinkedException;
            }

            $this->touchConnection($connection, $socialiteUser);

            return $connection;
        }

        return $this->createConnection($user, $provider, $socialiteUser);
    }

    private function findConnection(string $provider, SocialiteUser $socialiteUser): ?OAuthConnection
    {
        return OAuthConnection::query()
            ->where('provider', '=', $provider)
            ->where('provider_id', '=', (string) $socialiteUser->getId())
            ->first();
    }

    private function createConnection(User $user, string $provider, SocialiteUser $socialiteUser): OAuthConnection
    {
        return $user->oauthConnections()->create([
            'provider' => $provider,
            'provider_id' => (string) $socialiteUser->getId(),
            'name' => $socialiteUser->getName(),
            'email' => $socialiteUser->getEmail(),
            'last_used_at' => now(),
        ]);
    }

    private function touchConnection(OAuthConnection $connection, SocialiteUser $socialiteUser): void
    {
        $connection->forceFill([
            'name' => $socialiteUser->getName(),
            'email' => $socialiteUser->getEmail(),
            'last_used_at' => now(),
        ])->save();
    }

    private function provisionUser(string $provider, SocialiteUser $socialiteUser): User
    {
        $email = $socialiteUser->getEmail();

        if (! $this->emailIsVerified($provider, $socialiteUser) || blank($email)) {
            // Never auto-create an account from an unverified/absent email — that would let
            // anyone claim a colleague's address at their IdP and land in a fresh Convoy user.
            throw new OAuthAccountNotProvisionedException;
        }

        $user = new User;
        $user->forceFill([
            'name' => $socialiteUser->getName() ?: Str::before($email, '@'),
            'email' => $email,
            // A verified IdP is the credential; the password is a throwaway they never use.
            'password' => Str::random(24).'aA1!',
            'root_admin' => false,
            'email_verified_at' => now(),
        ])->save();

        $this->createConnection($user, $provider, $socialiteUser);

        return $user;
    }

    private function emailIsVerified(string $provider, SocialiteUser $socialiteUser): bool
    {
        if (blank($socialiteUser->getEmail())) {
            return false;
        }

        if (in_array($provider, self::IMPLICITLY_VERIFIED_EMAIL_PROVIDERS, true)) {
            return true;
        }

        $raw = method_exists($socialiteUser, 'getRaw') ? $socialiteUser->getRaw() : [];

        return filter_var($raw['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }
}
