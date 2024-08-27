<?php

use Convoy\Http\Controllers\Client;
use Convoy\Http\Middleware\Activity\ServerSubject;
use Convoy\Http\Middleware\Client\Server\AuthenticateServerAccess;
use Illuminate\Support\Facades\Route;

Route::get('/user', Client\SessionController::class);

Route::get('/servers', [Client\Servers\ServerController::class, 'index']);

Route::prefix('/servers/{server}')->middleware(
    [ServerSubject::class, AuthenticateServerAccess::class],
)->group(function () {
    Route::get('/', [Client\Servers\ServerController::class, 'show'])
         ->name('servers.show');

    Route::get(
        '/state', [Client\Servers\ServerController::class, 'getState'],
    );
    Route::patch(
        '/state', [Client\Servers\ServerController::class, 'updateState'],
    );

    Route::post(
        '/create-console-session',
        [Client\Servers\ServerController::class, 'createConsoleSession'],
    );

    Route::get('/addresses', Client\Servers\AddressController::class);

    Route::get('/statistics', Client\Servers\StatisticController::class);
    
    Route::prefix('/backups')->group(function () {
        Route::get('/', [Client\Servers\BackupController::class, 'index']);
        Route::post('/', [Client\Servers\BackupController::class, 'store'],
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
            '/rename', [Client\Servers\SettingsController::class, 'rename'],
        );
        Route::get(
            '/template-groups',
            [Client\Servers\SettingsController::class, 'getTemplateGroups'],
        );
        Route::post(
            '/reinstall',
            [Client\Servers\SettingsController::class, 'reinstall'],
        );

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
