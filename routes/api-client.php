<?php

use App\Http\Controllers\Client;
use App\Http\Middleware\Activity\ServerSubject;
use App\Http\Middleware\Client\Server\AuthenticateServerAccess;
use App\Http\Middleware\RequireIdentityConfirmation;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\RecoveryCodeController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\TwoFactorQrCodeController;
use Laravel\Fortify\Http\Controllers\TwoFactorSecretKeyController;

Route::get('/user', Client\SessionController::class);

Route::prefix('/account')->group(function () {
    Route::put('/password', [Client\PasswordController::class, 'update']);

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
            //Route::post('/confirm', [ConfirmedTwoFactorAuthenticationController::class, 'store']);
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

    Route::prefix('/snapshots')->group(function () {
        Route::get('/', [Client\Servers\SnapshotController::class, 'index']);
        Route::post('/', [Client\Servers\SnapshotController::class, 'store']);
        Route::post('/{snapshot}/restore', [Client\Servers\SnapshotController::class, 'restore']);
        Route::delete('/{snapshot}', [Client\Servers\SnapshotController::class, 'destroy']);
    });

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
