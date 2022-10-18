<?php

use Convoy\Http\Controllers\Client\IndexController;
use Convoy\Http\Controllers\Client\Servers\ActivityController;
use Convoy\Http\Controllers\Client\Servers\BackupController;
use Convoy\Http\Controllers\Client\Servers\CloudinitController;
use Convoy\Http\Controllers\Client\Servers\LogsController;
use Convoy\Http\Controllers\Client\Servers\PowerController;
use Convoy\Http\Controllers\Client\Servers\SecurityController;
use Convoy\Http\Controllers\Client\Servers\ServerController;
use Convoy\Http\Controllers\Client\Servers\SettingsController;
use Convoy\Http\Controllers\Client\Servers\SnapshotController;
use Convoy\Http\Controllers\Client\Servers\StatusController;
use Convoy\Http\Middleware\Activity\ServerSubject;
use Convoy\Http\Middleware\CheckServerInstalling;
use Convoy\Http\Middleware\CheckServerNotInstalling;
use Convoy\Http\Middleware\Client\Server\AuthenticateServerAccess;
use Illuminate\Support\Facades\Route;

Route::get('/', [IndexController::class, 'index'])->name('dashboard');

Route::get('/verify-auth-state', [IndexController::class, 'verifyAuthState'])->name('auth-state');

/*
|--------------------------------------------------------------------------
| Server Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /servers/{server}
|
*/

Route::group([
    'prefix' => '/servers/{server}',
    'middleware' => [
        ServerSubject::class,
        AuthenticateServerAccess::class,
        CheckServerInstalling::class,
    ],
], function () {
    Route::get('/', [ServerController::class, 'show'])->name('servers.show');
    Route::get('/templates', [SettingsController::class, 'getTemplates'])->name('servers.show.templates');
    Route::get('/building', [ServerController::class, 'showBuilding'])->name('servers.show.building');
    Route::get('/suspended', [ServerController::class, 'showSuspended'])->name('servers.show.suspended');

    Route::get('/status', [StatusController::class, 'show'])->name('servers.show.status');
    Route::post('/status', [PowerController::class, 'sendCommand']);

    Route::get('/details', [ServerController::class, 'getDetails'])->name('servers.show.details');

    Route::get('/activity', ActivityController::class)->name('servers.show.activity');

    /*
    |--------------------------------------------------------------------------
    | Snapshots API
    |--------------------------------------------------------------------------
    |
    | Endpoint: /servers/{server}/snapshots
    |
    */

    Route::prefix('/snapshots')->group(function () {
        Route::get('/', [SnapshotController::class, 'index'])->name('servers.show.snapshots');
        Route::post('/', [SnapshotController::class, 'store']);
        Route::delete('/', [SnapshotController::class, 'destroy']);
        Route::post('/rollback', [SnapshotController::class, 'rollback'])->name('servers.show.snapshots.rollback');
    });

    /*
    |--------------------------------------------------------------------------
    | Backups API
    |--------------------------------------------------------------------------
    |
    | Endpoint: /servers/{server}/backups
    |
    */

    Route::prefix('/backups')->group(function () {
        Route::get('/', [BackupController::class, 'index'])->name('servers.show.backups');
        Route::post('/', [BackupController::class, 'store']);
        Route::delete('/', [BackupController::class, 'destroy']);
        Route::post('/rollback', [BackupController::class, 'restore'])->name('servers.show.backups.rollback');
    });

    /*
    |--------------------------------------------------------------------------
    | Logs API
    |--------------------------------------------------------------------------
    |
    | Endpoint: /servers/{server}/logs
    |
    */

    Route::prefix('/logs')->group(function () {
        Route::get('/', [LogsController::class, 'index'])->name('servers.show.logs');
        Route::get('/json', [LogsController::class, 'getLogs'])->name('servers.show.logs.json');
    });

    /*
    |--------------------------------------------------------------------------
    | Security API
    |--------------------------------------------------------------------------
    |
    | Endpoint: /servers/{server}/security
    |
    */

    Route::prefix('/security')->group(function () {
        Route::get('/', [SecurityController::class, 'index'])->name('servers.show.security');

        Route::prefix('/vnc')->group(function () {
            Route::get('/', [SecurityController::class, 'showVnc'])->name('servers.show.security.vnc');
            Route::get('/credentials', [SecurityController::class, 'getVncCredentials'])->name('servers.show.security.vnc.credentials');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Settings Controller Routes
    |--------------------------------------------------------------------------
    |
    | Endpoint: /servers/{server}/settings
    |
    */

    Route::prefix('/settings')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('servers.show.settings');
        Route::patch('/basic-info', [SettingsController::class, 'updateBasicInfo'])->name('servers.show.settings.basic-info');
        Route::put('/password', [CloudinitController::class, 'updatePassword'])->name('servers.show.settings.password');
        Route::put('/bios', [CloudinitController::class, 'updateBios'])->name('servers.show.settings.bios');
        Route::put('/network-config', [CloudinitController::class, 'updateNetworkConfig'])->name('servers.show.settings.network-config');
        Route::post('/rebuild', [SettingsController::class, 'rebuild'])->name('servers.show.settings.rebuild');
    });
});
