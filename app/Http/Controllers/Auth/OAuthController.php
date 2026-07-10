<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\HasErrorCode;
use App\Models\User;
use App\Services\Auth\OAuthAuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

/**
 * Convoy as an OAuth/OIDC Relying Party. A logged-out user federates to sign in; a logged-in user
 * federates to *link* the provider to their existing account. Both directions funnel through the
 * same provider redirect → callback pair; the callback branches on the current auth state.
 *
 * These are browser-redirect endpoints, so failures are surfaced by redirecting back to the SPA
 * with an `oauth_error` code the frontend renders — not as JSON (the exception render hook only
 * fires for `expectsJson()` requests).
 */
class OAuthController
{
    private const LOGIN_PATH = '/auth/login';

    private const ACCOUNT_SECURITY_PATH = '/security';

    public function __construct(
        private readonly OAuthAuthenticationService $service,
    ) {}

    /**
     * Kick off the provider handshake. Remembers where the SPA wanted to land so the callback can
     * honour it after a successful login.
     */
    public function redirect(Request $request, string $provider): SymfonyRedirectResponse
    {
        $this->service->ensureEnabled($provider);

        $intended = (string) $request->query('intended', '');
        // Only same-origin relative paths — never an absolute URL an attacker could smuggle in as
        // an open-redirect after login.
        $request->session()->put(
            'oauth.intended',
            str_starts_with($intended, '/') && ! str_starts_with($intended, '//') ? $intended : '/',
        );

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle the provider's redirect back. Logged-in → link; logged-out → login/provision.
     */
    public function callback(Request $request, string $provider): RedirectResponse
    {
        $this->service->ensureEnabled($provider);

        try {
            $socialiteUser = Socialite::driver($provider)->user();
        } catch (InvalidStateException) {
            // Stale/forged state (e.g. the user sat on the provider page past the session, or an
            // out-of-band callback). Not an error worth a stack trace — send them back to retry.
            return $this->failLogin('oauth_invalid_state');
        }

        if (Auth::check()) {
            return $this->handleLink($request, $provider, $socialiteUser);
        }

        return $this->handleLogin($request, $provider, $socialiteUser);
    }

    private function handleLogin(Request $request, string $provider, \Laravel\Socialite\Contracts\User $socialiteUser): RedirectResponse
    {
        try {
            $user = $this->service->resolveForLogin($provider, $socialiteUser);
        } catch (HasErrorCode $e) {
            return $this->failLogin($e->errorCode());
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        $intended = (string) $request->session()->pull('oauth.intended', '/');

        return redirect()->to($intended === '' ? '/' : $intended);
    }

    private function handleLink(Request $request, string $provider, \Laravel\Socialite\Contracts\User $socialiteUser): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $request->session()->forget('oauth.intended');

        try {
            $this->service->linkToUser($user, $provider, $socialiteUser);
        } catch (HasErrorCode $e) {
            return redirect()->to(self::ACCOUNT_SECURITY_PATH.'?oauth_error='.$e->errorCode());
        }

        return redirect()->to(self::ACCOUNT_SECURITY_PATH.'?oauth_linked='.$provider);
    }

    private function failLogin(string $code): RedirectResponse
    {
        return redirect()->to(self::LOGIN_PATH.'?oauth_error='.$code);
    }
}
