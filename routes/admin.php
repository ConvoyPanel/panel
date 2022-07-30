<?php

use App\Http\Controllers\Admin\IndexController;
use App\Http\Controllers\Admin\Nodes\Addresses\AddressController;
use App\Http\Controllers\Admin\Nodes\NodeController;
use App\Http\Controllers\Admin\Nodes\SettingsController;
use App\Http\Controllers\Admin\Servers\ServerController;
use App\Http\Controllers\Admin\Users\UserController;
use App\Http\Controllers\Admin\Servers\Settings;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => '/admin', 'as' => 'admin.'], function () {
    Route::get('/', [IndexController::class, 'index'])->name('dashboard');

    Route::group(['prefix' => '/nodes', 'as' => 'nodes.'], function () {
        Route::get('/', [NodeController::class, 'index'])->name('index');

        Route::get('/create', [NodeController::class, 'create'])->name('create');

        Route::post('/', [NodeController::class, 'store'])->name('store');

        Route::get('/search', [NodeController::class, 'search'])->name('search');

        Route::group(['prefix' => '/{node}'], function () {
            Route::get('/', [NodeController::class, 'show'])->name('show');

            Route::put('/', [NodeController::class, 'update'])->name('update');

            Route::delete('/', [NodeController::class, 'destroy'])->name('destroy');

            Route::group(['as' => 'show.'], function () {
                Route::group(['prefix' => '/addresses', 'as' => 'addresses.'], function () {
                    Route::get('/', [AddressController::class, 'index'])->name('index');

                    Route::get('/search', [AddressController::class, 'search'])->name('search');

                    Route::post('/', [AddressController::class, 'store'])->name('store');

                    Route::group(['prefix' => '/{address}'], function () {

                        Route::put('/', [AddressController::class, 'update'])->name('update');

                        Route::delete('/', [AddressController::class, 'destroy'])->name('destroy');
                    });
                });

                Route::group(['prefix' => '/settings', 'as' => 'settings.'], function () {
                    Route::get('/', [SettingsController::class, 'index'])->name('index');

                    Route::put('/basic-settings', [NodeController::class, 'update'])->name('update');
                });
            });
        });
    });

    Route::group(['prefix' => '/servers', 'as' => 'servers.'], function () {
        Route::get('/', [ServerController::class, 'index'])->name('index');

        Route::get('/create', [ServerController::class, 'create'])->name('create');

        Route::post('/', [ServerController::class, 'store'])->name('store');

        Route::get('/search', [ServerController::class, 'search'])->name('search');

        Route::group(['prefix' => '/{server}'], function () {
            Route::get('/', [ServerController::class, 'show'])->name('show');

            Route::delete('/', [ServerController::class, 'destroy'])->name('destroy');

            Route::group(['as' => 'show.'], function () {
                Route::group(['prefix' => '/settings', 'as' => 'settings.'], function () {
                    Route::get('/', [Settings\SettingsController::class, 'index'])->name('index');

                    Route::put('/basic-info', [Settings\SettingsController::class, 'updateBasicInfo'])->name('update-basic-info');
                });
            });
        });
    });

    Route::group(['prefix' => '/users', 'as' => 'users.'], function () {
        Route::get('/', [UserController::class, 'index'])->name('index');

        Route::get('/search', [UserController::class, 'search'])->name('search');
    });
});
