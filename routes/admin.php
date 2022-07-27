<?php

use App\Http\Controllers\Admin\IndexController;
use App\Http\Controllers\Admin\Nodes\Addresses\AddressController;
use App\Http\Controllers\Admin\Nodes\NodeController;
use App\Http\Controllers\Admin\Nodes\SettingsController;
use App\Http\Controllers\Admin\Servers\ServerController;
use App\Http\Controllers\Admin\Users\UserController;
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

            Route::group(['as' => 'show.'], function () {
                Route::group(['prefix' => '/addresses', 'as' => 'addresses.'], function () {
                    Route::get('/', [AddressController::class, 'index'])->name('index');

                    Route::post('/', [AddressController::class, 'store'])->name('store');

                    Route::group(['prefix' => '/{address}'], function () {

                        Route::put('/', [AddressController::class, 'update'])->name('update');

                        Route::delete('/', [AddressController::class, 'destroy'])->name('destroy');
                    });
                });

                Route::group(['prefix' => '/settings', 'as' => 'settings.'], function () {
                    Route::get('/', [SettingsController::class, 'index'])->name('index');
                });
            });
        });
    });

    Route::group(['prefix' => '/servers', 'as' => 'servers.'], function () {
        Route::get('/', [ServerController::class, 'index'])->name('index');

        Route::get('/search', [ServerController::class, 'search'])->name('search');
    });

    Route::group(['prefix' => '/users', 'as' => 'users.'], function () {
        Route::get('/', [UserController::class, 'index'])->name('index');

        Route::get('/search', [UserController::class, 'search'])->name('search');
    });
});
