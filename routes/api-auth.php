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

    Route::get('/passkeys/authentication-options', [Auth\PasskeyLoginController::class, 'create']);
    Route::post('/passkeys/verify-authentication', [Auth\PasskeyLoginController::class, 'store']);

    $twoFactorLimiter = config('fortify.limiters.two-factor');
    Route::post('/authenticator/verify-challenge', [TwoFactorAuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:'.$twoFactorLimiter);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);

    /* Confirmable Identity Routes */
    Route::prefix('/identity')->group(function () {
        Route::get('/passkey-authentication-options', [Auth\ConfirmableIdentityController::class, 'generatePasskeyAuthOptions']);
        Route::post('/confirm', [Auth\ConfirmableIdentityController::class, 'store']);
    });
});
