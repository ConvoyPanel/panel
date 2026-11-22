<?php

use App\Http\Controllers\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController;

/*
|--------------------------------------------------------------------------
| Authentication Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /auth
|
*/

// OAuth/OIDC federated login (Convoy as Relying Party). Outside the guest/auth groups on purpose:
// a logged-out user hits these to sign in, a logged-in user hits them to link a provider — the
// callback branches on the current auth state. Web session applies (Socialite needs it for state).
Route::get('/oauth/{provider}/redirect', [Auth\OAuthController::class, 'redirect'])
    ->name('auth.oauth.redirect');
Route::get('/oauth/{provider}/callback', [Auth\OAuthController::class, 'callback'])
    ->name('auth.oauth.callback');

/*
 | Redeeming an invite. Outside the guest group on purpose: the token in the URL *is* the
 | credential, so it — not the session — decides what happens. Inside `guest`, an admin opening
 | the link they just minted to check it would be bounced to the dashboard, and a second attempt
 | to spend a link would answer with a redirect instead of saying plainly that it is dead.
 |
 | Throttled on the login limiter, since this is an unauthenticated endpoint that hands out a
 | session.
 */
Route::middleware('throttle:'.config('fortify.limiters.login'))->group(function () {
    Route::get('/invite/{token}', [Auth\InviteController::class, 'show']);
    Route::post('/invite/{token}', [Auth\InviteController::class, 'store']);
    Route::get('/invite/{token}/passkey-options', [Auth\InviteController::class, 'passkeyOptions']);
    Route::post('/invite/{token}/passkey', [Auth\InviteController::class, 'storePasskey']);
});

Route::middleware('guest')->group(function () {
    // Single sign-on deep link (minted by Admin\UserController::getSSOToken). The `signed`
    // middleware verifies the HMAC + expiry; the controller enforces single use. Named so
    // `URL::temporarySignedRoute('auth.sso.consume', …)` can address it.
    Route::get('/sso/{uuid}', [Auth\SsoController::class, 'consume'])
        ->middleware('signed')
        ->name('auth.sso.consume');

    $loginLimiter = config('fortify.limiters.login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:'.$loginLimiter);

    /*
     | Forgot password. Every one of these answers 404 unless an operator has turned resets on
     | and the panel has a relay to send them with. Asking for a link gets its own, tighter
     | limiter: each request can send an email, and the login limiter is keyed per address, so
     | it would let one client mail a different address every request.
     */
    Route::post('/password-reset', [Auth\PasswordResetController::class, 'store'])
        ->middleware('throttle:password-reset');
    Route::middleware('throttle:'.$loginLimiter)->group(function () {
        Route::get('/password-reset/{token}', [Auth\PasswordResetController::class, 'show']);
        Route::post('/password-reset/{token}', [Auth\PasswordResetController::class, 'update']);
    });

    Route::get('/passkeys/authentication-options', [Auth\PasskeyLoginController::class, 'create']);
    Route::post('/passkeys/verify-authentication', [Auth\PasskeyLoginController::class, 'store']);

    $twoFactorLimiter = config('fortify.limiters.two-factor');
    Route::post('/authenticator/verify-challenge', [TwoFactorAuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:'.$twoFactorLimiter);

    Route::prefix('/second-factor')->middleware('throttle:'.$twoFactorLimiter)->group(function () {
        Route::get('/', [Auth\SecondFactorChallengeController::class, 'show']);
        Route::get('/passkey-options', [Auth\SecondFactorChallengeController::class, 'create']);
        Route::post('/verify-passkey', [Auth\SecondFactorChallengeController::class, 'store']);
    });
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);

    /* Confirmable Identity Routes */
    Route::prefix('/identity')->group(function () {
        // The client asks rather than keeping its own copy of the window; the
        // answer comes from the same place the middleware reads. See
        // App\Auth\IdentityConfirmation.
        Route::get('/', [Auth\ConfirmableIdentityController::class, 'show']);
        Route::get('/passkey-authentication-options', [Auth\ConfirmableIdentityController::class, 'generatePasskeyAuthOptions']);
        Route::post('/confirm', [Auth\ConfirmableIdentityController::class, 'store']);
    });
});
