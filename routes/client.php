<?php

use App\Http\Controllers\Client\IndexController;
use App\Http\Controllers\Client\Servers\BackupController;
use App\Http\Controllers\Client\Servers\CloudinitController;
use App\Http\Controllers\Client\Servers\LogsController;
use App\Http\Controllers\Client\Servers\PowerController;
use App\Http\Controllers\Client\Servers\SecurityController;
use App\Http\Controllers\Client\Servers\ServerController;
use App\Http\Controllers\Client\Servers\StatusController;
use App\Http\Middleware\AuthenticateServerAccess;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Client\Servers\SettingsController;
use App\Http\Controllers\Client\Servers\SnapshotController;
use App\Http\Middleware\CheckServerInstalling;
use App\Http\Middleware\CheckServerNotInstalling;

Route::get('/', [IndexController::class, 'index'])->name('dashboard');

Route::get('/verify-auth-state', [IndexController::class, 'verifyAuthState'])->name('auth-state');


Route::group(['prefix' => '/servers/{server}', 'middleware' => [AuthenticateServerAccess::class, CheckServerInstalling::class]], function () {
    Route::get('/', [ServerController::class, 'show'])->name('servers.show');
    Route::get('/templates', [SettingsController::class, 'getTemplates'])->name('servers.show.templates');
    Route::get('/installing', [ServerController::class, 'showIsInstallingPage'])->middleware(CheckServerNotInstalling::class)->withoutMiddleware(CheckServerInstalling::class)->name('servers.show.installing');

    Route::get('/status', [StatusController::class, 'show'])->name('servers.show.status');
    Route::post('/status', [PowerController::class, 'sendCommand']);

    Route::get('/resources', [StatusController::class, 'getResources'])->name('servers.show.resources');

    Route::prefix('/snapshots')->group(function () {
        Route::get('/', [SnapshotController::class, 'index'])->name('servers.show.snapshots');
        Route::post('/', [SnapshotController::class, 'store']);
        Route::delete('/', [SnapshotController::class, 'destroy']);
        Route::post('/rollback', [SnapshotController::class, 'rollback'])->name('servers.show.snapshots.rollback');
    });

    Route::prefix('/backups')->group(function() {
        Route::get('/', [BackupController::class, 'index'])->name('servers.show.backups');
        Route::post('/', [BackupController::class, 'createBackup']);
        Route::delete('/', [BackupController::class, 'destroyBackup']);
        Route::post('/rollback', [BackupController::class, 'rollback'])->name('servers.show.backups.rollback');
    });

    Route::prefix('/logs')->group(function() {
        Route::get('/', [LogsController::class, 'index'])->name('servers.show.logs');
        Route::get('/json', [LogsController::class, 'getLogs'])->name('servers.show.logs.json');
    });

    Route::prefix('/security')->group(function() {
        Route::get('/', [SecurityController::class, 'index'])->name('servers.show.security');

        Route::prefix('/vnc')->group(function() {
            Route::get('/', [SecurityController::class, 'showVnc'])->name('servers.show.security.vnc');
            Route::get('/credentials', [SecurityController::class, 'getVncCredentials'])->name('servers.show.security.vnc.credentials');
        });
    });

    Route::prefix('/settings')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('servers.show.settings');
        Route::patch('/basic-info', [SettingsController::class, 'updateBasicInfo'])->name('servers.show.settings.basic-info');
        Route::put('/password', [CloudinitController::class, 'updatePassword'])->name('servers.show.settings.password');
        Route::put('/bios', [CloudinitController::class, 'updateBios'])->name('servers.show.settings.bios');
        Route::put('/network-config', [CloudinitController::class, 'updateNetworkConfig'])->name('servers.show.settings.network-config');
        Route::post('/reinstall', [SettingsController::class, 'reinstall'])->name('servers.show.settings.reinstall');
        Route::get('/cloudinit/dump', [CloudinitController::class, 'dumpConfig'])->name('servers.show.settings.cloudinit.config');
    });
});
/*
Route::group(['prefix' => '/servers/{server}', 'middleware' => [AuthenticateServerAccess::class, CheckServerInstalling::class], 'as' => 'servers.'], function () {
    Route::get('/', [ServerController::class, 'show'])->name('show');

    Route::get('/templates', [SettingsController::class, 'getTemplates'])->name('get-templates');

    Route::group(['as' => 'show.'], function () {
        Route::get('/installing', [ServerController::class, 'showIsInstallingPage'])->middleware(CheckServerNotInstalling::class)->withoutMiddleware(CheckServerInstalling::class)->name('installing.index');

        Route::get('/status', [StatusController::class, 'show'])->name('status');
        Route::post('/status', [PowerController::class, 'sendCommand'])->name('status.update');

        Route::get('/resources', [StatusController::class, 'getResources'])->name('get-resources');

        Route::group(['prefix' => '/snapshots', 'as' => 'snapshots.'], function () {
            Route::get('/', [SnapshotController::class, 'index'])->name('index');

            Route::post('/', [SnapshotController::class, 'store'])->name('store');

            Route::post('/rollback', [SnapshotController::class, 'rollback'])->name('rollback');

            Route::delete('/', [SnapshotController::class, 'destroy'])->name('destroy');
        });

        Route::group(['prefix' => '/backups', 'as' => 'backups.'], function () {
            Route::get('/', [BackupController::class, 'index'])->name('index');

            Route::post('/', [BackupController::class, 'createBackup'])->name('store');

            Route::delete('/', [BackupController::class, 'destroyBackup'])->name('destroy');

            Route::post('/rollback', [BackupController::class, 'rollback'])->name('rollback');
        });

        Route::group(['prefix' => '/logs', 'as' => 'logs.'], function () {
            Route::get('/', [LogsController::class, 'index'])->name('index');
            Route::get('/json', [LogsController::class, 'getLogs'])->name('get-logs');
        });

        Route::group(['prefix' => '/security', 'as' => 'security.'], function () {
            Route::get('/', [SecurityController::class, 'index'])->name('index');

            Route::group(['prefix' => '/vnc', 'as' => 'vnc.'], function () {
                Route::get('/', [SecurityController::class, 'showVnc'])->name('index');

                Route::get('/credentials', [SecurityController::class, 'getVncCredentials'])->name('get-credentials');
            });
        });

        Route::group(['prefix' => '/settings', 'as' => 'settings.'], function () {
            Route::get('/', [SettingsController::class, 'index'])->name('index');

            Route::patch('/basic-info', [SettingsController::class, 'updateBasicInfo'])->name('update-basic-info');

            Route::put('/password', [CloudinitController::class, 'updatePassword'])->name('update-password');

            Route::put('/bios', [CloudinitController::class, 'updateBios'])->name('update-bios');

            Route::put('/network-config', [CloudinitController::class, 'updateNetworkConfig'])->name('update-network-config');

            Route::post('/reinstall', [SettingsController::class, 'reinstall'])->name('reinstall');

            Route::get('/cloudinit/dump', [CloudinitController::class, 'dumpConfig'])->name('cloudinit.dump-config');
        });
    });
});
 */