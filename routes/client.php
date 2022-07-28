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

Route::get('/dashboard', [IndexController::class, 'index'])->name('dashboard');

Route::get('/verify-auth-state', [IndexController::class, 'verifyAuthState'])->name('verify-auth-state');

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

            Route::put('/update-password', [CloudinitController::class, 'updatePassword'])->name('update-password');

            Route::put('/update-bios', [CloudinitController::class, 'updateBios'])->name('update-bios');

            Route::put('/update-network-config', [CloudinitController::class, 'updateNetworkConfig'])->name('update-network-config');

            Route::post('/reinstall', [SettingsController::class, 'reinstall'])->name('reinstall');

            Route::get('/cloudinit/dump', [CloudinitController::class, 'dumpConfig'])->name('cloudinit.dump-config');
        });
    });
});
