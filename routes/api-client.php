<?php

use App\Http\Controllers\Client;
use App\Http\Middleware\Activity\ServerSubject;
use App\Http\Middleware\Client\Server\AuthenticateServerAccess;
use App\Http\Middleware\DenyApiTokenAccess;
use App\Http\Middleware\RequireIdentityConfirmation;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\ConfirmedTwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\RecoveryCodeController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\TwoFactorQrCodeController;
use Laravel\Fortify\Http\Controllers\TwoFactorSecretKeyController;

Route::get('/user', Client\SessionController::class);

// The whole account/security surface is session-only: an end-user API token must never be able to
// change the account it belongs to (password, 2FA, passkeys) or mint/revoke further tokens.
Route::prefix('/account')->middleware(DenyApiTokenAccess::class)->group(function () {
    Route::put('/password', [Client\PasswordController::class, 'update']);

    // Reads stay ungated: both lists render straight onto the security page, so
    // requiring confirmation to *see* them would gate the page itself. Writes
    // mint credentials that outlive the session that created them — an API token
    // survives logout and a password change, an SSH key grants server access —
    // so they need the same identity confirmation as passkeys and 2FA. Without
    // it a live session alone (unattended browser, stolen cookie) could mint a
    // persistent credential while being unable to so much as view a 2FA QR code.
    Route::prefix('/api-keys')->group(function () {
        Route::get('/', [Client\Account\ApiKeyController::class, 'index']);

        Route::middleware(RequireIdentityConfirmation::class)->group(function () {
            Route::post('/', [Client\Account\ApiKeyController::class, 'store']);
            Route::delete('/{apiKey}', [Client\Account\ApiKeyController::class, 'destroy'])
                ->withoutScopedBindings();
        });
    });

    Route::prefix('/ssh-keys')->group(function () {
        Route::get('/', [Client\Account\SSHKeyController::class, 'index']);

        Route::middleware(RequireIdentityConfirmation::class)->group(function () {
            Route::post('/', [Client\Account\SSHKeyController::class, 'store']);
            Route::delete('/{sshKey}', [Client\Account\SSHKeyController::class, 'destroy'])
                ->withoutScopedBindings();
        });
    });

    Route::prefix('/sessions')->group(function () {
        Route::get('/', [Client\Account\SessionRecordController::class, 'index']);
        Route::delete('/{sessionRecord}', [Client\Account\SessionRecordController::class, 'destroy'])
            ->withoutScopedBindings();
    });

    // Federated (OAuth/OIDC) identities linked to the account. Linking itself is a browser redirect
    // flow (Auth\OAuthController) — only listing/unlinking are JSON here.
    Route::prefix('/oauth-connections')->group(function () {
        Route::get('/', [Client\Account\OAuthConnectionController::class, 'index']);
        Route::delete('/{oauthConnection}', [Client\Account\OAuthConnectionController::class, 'destroy'])
            ->withoutScopedBindings();
    });

    Route::prefix('/passkeys')
        ->middleware(RequireIdentityConfirmation::class)
        ->group(function () {
            Route::get('/', [Client\PasskeyController::class, 'index']);
            Route::get('/registration-options', [Client\PasskeyController::class, 'create']);
            Route::post('/verify-registration', [Client\PasskeyController::class, 'store']);

            Route::middleware('can:update,passkey')->group(function () {
                Route::post('/{passkey}/rename', [Client\PasskeyController::class, 'rename']);
                Route::delete('/{passkey}', [Client\PasskeyController::class, 'destroy']);
            });
        });

    Route::get('/authenticator/status', Client\AuthenticatorStatusController::class);
    Route::prefix('/authenticator')
        ->middleware(RequireIdentityConfirmation::class)
        ->group(function () {
            Route::post('/enable', [TwoFactorAuthenticationController::class, 'store']);
            Route::post('/disable', [TwoFactorAuthenticationController::class, 'destroy']);
            // Proves the user actually scanned the secret. `enable` only mints
            // it; nothing is enabled until a generated code lands here.
            Route::post('/confirm', [ConfirmedTwoFactorAuthenticationController::class, 'store']);
            Route::get('/qr-code', [TwoFactorQrCodeController::class, 'show']);
            Route::get('/secret-key', [TwoFactorSecretKeyController::class, 'show']);
            Route::get('/recovery-codes', [RecoveryCodeController::class, 'index']);
            Route::post('/recovery-codes/regenerate', [RecoveryCodeController::class, 'store']);
        });
});

Route::get('/servers', [Client\Servers\ServerController::class, 'index']);

Route::prefix('/servers/{server}')->middleware(
    [ServerSubject::class, AuthenticateServerAccess::class],
)->group(function () {
    Route::get('/', [Client\Servers\ServerController::class, 'show'])
        ->name('servers.show');

    Route::get(
        '/deployment',
        [Client\Servers\ServerController::class, 'getDeployment'],
    )->name('servers.show.deployment');

    Route::post(
        '/retry-installation',
        [Client\Servers\ServerController::class, 'retryInstallation'],
    )->name('servers.show.retry-installation');

    Route::get(
        '/state',
        [Client\Servers\ServerController::class, 'getState'],
    )->name('servers.show.state');
    Route::patch(
        '/state',
        [Client\Servers\ServerController::class, 'updateState'],
    );

    Route::post(
        '/create-console-session',
        [Client\Servers\ServerController::class, 'createConsoleSession'],
    );

    Route::get('/addresses', Client\Servers\AddressController::class);

    Route::get('/statistics', Client\Servers\StatisticController::class);
    Route::get('/resources', Client\Servers\ResourceController::class);

    Route::prefix('/backups')->group(function () {
        Route::get('/', [Client\Servers\BackupController::class, 'index']);
        Route::post(
            '/',
            [Client\Servers\BackupController::class, 'store'],
        );
        Route::post(
            '/{backup}/restore',
            [Client\Servers\BackupController::class, 'restore'],
        );
        Route::delete(
            '/{backup}',
            [Client\Servers\BackupController::class, 'destroy'],
        );
    });

    Route::prefix('/settings')->group(function () {
        Route::post(
            '/rename',
            [Client\Servers\SettingsController::class, 'rename'],
        );
        Route::get(
            '/template-groups',
            [Client\Servers\SettingsController::class, 'getTemplateGroups'],
        )->name('servers.template-groups.index');
        Route::post(
            '/reinstall',
            [Client\Servers\SettingsController::class, 'reinstall'],
        )->name('servers.show.reinstall');

        Route::get(
            '/hardware/boot-order',
            [Client\Servers\SettingsController::class, 'getBootOrder'],
        );
        Route::put(
            '/hardware/boot-order',
            [Client\Servers\SettingsController::class, 'updateBootOrder'],
        );

        Route::get(
            '/hardware/isos',
            [Client\Servers\SettingsController::class, 'getMedia'],
        );
        Route::post(
            '/hardware/isos/{iso}/mount',
            [Client\Servers\SettingsController::class, 'mountMedia'],
        )->withoutScopedBindings();
        Route::post(
            '/hardware/isos/{iso}/unmount',
            [Client\Servers\SettingsController::class, 'unmountMedia'],
        )->withoutScopedBindings();

        Route::get(
            '/network',
            [Client\Servers\SettingsController::class, 'getNetworkSettings'],
        );
        Route::put(
            '/network',
            [Client\Servers\SettingsController::class, 'updateNetworkSettings'],
        );

        Route::get(
            '/auth',
            [Client\Servers\SettingsController::class, 'getAuthSettings'],
        );
        Route::put(
            '/auth',
            [Client\Servers\SettingsController::class, 'updateAuthSettings'],
        );
    });
});
