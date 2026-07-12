<?php

namespace App\Auth\Socialite;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;
use RuntimeException;

/**
 * A generic OpenID Connect (OIDC) Socialite driver, so Convoy can federate against *any*
 * standards-compliant IdP (Keycloak, Authentik, Okta, Auth0, Azure AD, Zitadel, …) rather
 * than only the three hand-rolled drivers Socialite ships.
 *
 * The three protocol endpoints (authorize / token / userinfo) are resolved from the IdP's
 * discovery document at `{issuer}/.well-known/openid-configuration`, so an operator only
 * has to supply the issuer URL plus a client id/secret. Each endpoint may still be pinned
 * explicitly in `config/services.php` for IdPs with a non-standard discovery URL.
 */
class OidcProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * OIDC uses space-delimited scopes and always needs `openid`; `profile` and `email`
     * are what we map into the Convoy user. Operators may override via config.
     *
     * @var array<int, string>
     */
    protected $scopes = ['openid', 'profile', 'email'];

    protected $scopeSeparator = ' ';

    /**
     * Cached discovery document for this request lifecycle.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $discovery = null;

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->endpoint('authorization_endpoint'), $state);
    }

    protected function getTokenUrl(): string
    {
        return $this->endpoint('token_endpoint');
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get($this->endpoint('userinfo_endpoint'), [
            RequestOptions::HEADERS => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true) ?: [];
    }

    /**
     * {@inheritdoc}
     *
     * Maps the standard OIDC claims. `email_verified` is kept in the raw payload so
     * OAuthAuthenticationService can gate auto-link/provision on it.
     */
    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user)->map([
            'id' => Arr::get($user, 'sub'),
            'nickname' => Arr::get($user, 'preferred_username'),
            'name' => Arr::get($user, 'name'),
            'email' => Arr::get($user, 'email'),
            'avatar' => Arr::get($user, 'picture'),
        ]);
    }

    /**
     * Resolve a protocol endpoint, preferring an explicit `config/services.php` override
     * and otherwise reading it from the cached discovery document.
     */
    protected function endpoint(string $key): string
    {
        $override = $this->getConfig($this->overrideKey($key));

        if (filled($override)) {
            return (string) $override;
        }

        $value = Arr::get($this->discover(), $key);

        if (! filled($value)) {
            throw new RuntimeException(
                "OIDC discovery for issuer \"{$this->getConfig('base_url')}\" is missing \"{$key}\"."
            );
        }

        return (string) $value;
    }

    /**
     * The `config/services.php` key that pins a given discovery endpoint explicitly.
     */
    protected function overrideKey(string $discoveryKey): string
    {
        return match ($discoveryKey) {
            'authorization_endpoint' => 'auth_url',
            'token_endpoint' => 'token_url',
            'userinfo_endpoint' => 'userinfo_url',
            default => $discoveryKey,
        };
    }

    /**
     * Fetch and cache the IdP's discovery document. Cached for an hour keyed by issuer so a
     * login flurry doesn't hammer the IdP's well-known endpoint.
     *
     * @return array<string, mixed>
     */
    protected function discover(): array
    {
        if (is_array($this->discovery)) {
            return $this->discovery;
        }

        $issuer = rtrim((string) $this->getConfig('base_url'), '/');

        if ($issuer === '') {
            throw new RuntimeException('OIDC provider requires a "base_url" (issuer) in config/services.php.');
        }

        return $this->discovery = Cache::remember(
            'oidc.discovery:'.md5($issuer),
            now()->addHour(),
            function () use ($issuer): array {
                $response = $this->getHttpClient()->get($issuer.'/.well-known/openid-configuration', [
                    RequestOptions::HEADERS => ['Accept' => 'application/json'],
                ]);

                $document = json_decode((string) $response->getBody(), true);

                if (! is_array($document)) {
                    throw new RuntimeException("OIDC discovery at issuer \"{$issuer}\" returned an invalid document.");
                }

                return $document;
            }
        );
    }

    /**
     * Read a value from this driver's `config/services.php` block. Socialite's manager only
     * hands the constructor the client id/secret/redirect, so anything extra (issuer, scopes,
     * endpoint overrides) is read straight from config here.
     */
    protected function getConfig(string $key): mixed
    {
        return config("services.oidc.{$key}");
    }
}
