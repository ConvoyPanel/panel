<?php

use App\Http\Controllers\Admin\IndexController;
use App\Http\Controllers\Admin\Nodes\Addresses\AddressController;
use App\Http\Controllers\Admin\Nodes\NodeController;
use App\Http\Controllers\Admin\Nodes\SettingsController;
use App\Http\Controllers\Admin\Servers\ServerController;
use App\Http\Controllers\Admin\Users\UserController;
use App\Http\Controllers\Admin\Servers\Settings;
use App\Http\Controllers\Admin\Users;
use Illuminate\Support\Facades\Route;

Route::get('/', [IndexController::class, 'index'])->name('admin.dashboard');

Route::prefix('/nodes')->group(function () {
    Route::get('/', [NodeController::class, 'index'])->name('admin.nodes');
    Route::post('/', [NodeController::class, 'store']);
    Route::get('/create', [NodeController::class, 'create'])->name('admin.nodes.create');
    Route::get('/search', [NodeController::class, 'search'])->name('admin.nodes.search');

    Route::prefix('/{node}')->group(function () {
        Route::get('/', [NodeController::class, 'show'])->name('admin.nodes.show');
        Route::delete('/', [NodeController::class, 'destroy']);

        Route::prefix('/addresses')->group(function () {
            Route::get('/', [AddressController::class, 'index'])->name('admin.nodes.show.addresses');
            Route::post('/', [AddressController::class, 'store']);
            Route::get('/search', [AddressController::class, 'search'])->name('admin.nodes.show.addresses.search');

            Route::prefix('/{address}')->group(function () {
                Route::put('/', [AddressController::class, 'update'])->name('admin.nodes.show.addresses.show');
                Route::delete('/', [AddressController::class, 'destroy']);
            });
        });

        Route::prefix('/settings')->group(function () {
            Route::get('/', [SettingsController::class, 'index'])->name('admin.nodes.show.settings');
            Route::put('/basic-settings', [NodeController::class, 'update'])->name('admin.nodes.show.settings.basic-info');
        });
    });
});

Route::prefix('/servers')->group(function () {
    Route::get('/', [ServerController::class, 'index'])->name('admin.servers');
    Route::post('/', [ServerController::class, 'store']);
    Route::get('/create', [ServerController::class, 'create'])->name('admin.servers.create');
    Route::get('/search', [ServerController::class, 'search'])->name('admin.servers.search');

    Route::prefix('/{server}')->group(function () {
        Route::get('/', [ServerController::class, 'show'])->name('admin.servers.show');
        Route::delete('/', [ServerController::class, 'destroy']);

        Route::prefix('/settings')->group(function () {
            Route::get('/', [Settings\SettingsController::class, 'index'])->name('admin.servers.show.settings');
            Route::put('/basic-info', [Settings\SettingsController::class, 'updateBasicInfo'])->name('admin.servers.show.settings.basic-info');
        });
    });
});

Route::prefix('/users')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('admin.users');
    Route::post('/', [UserController::class, 'store']);
    Route::get('/search', [UserController::class, 'search'])->name('admin.users.search');

    Route::prefix('/{user}')->group(function () {
        Route::get('/', [UserController::class, 'show'])->name('admin.users.show');
        Route::delete('/', [UserController::class, 'destroy']);

        Route::prefix('/settings')->group(function () {
            Route::get('/', [Users\Settings\SettingsController::class, 'index'])->name('admin.users.show.settings');
            Route::put('/', [Users\Settings\SettingsController::class, 'update']);
        });
    });
});


/* Route::group(['prefix' => '/admin', 'as' => 'admin.'], function () {
    Route::get('/', [IndexController::class, 'index'])->name('dashboard');

    Route::group(['prefix' => '/nodes', 'as' => 'nodes.'], function () {
        Route::get('/', [NodeController::class, 'index'])->name('index');

        Route::get('/create', [NodeController::class, 'create'])->name('create');

        Route::post('/', [NodeController::class, 'store'])->name('store');

        Route::get('/search', [NodeController::class, 'search'])->name('search');

        Route::group(['prefix' => '/{node}'], function () {
            Route::get('/', [NodeController::class, 'show'])->name('show');

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

        Route::post('/', [UserController::class, 'store'])->name('store');

        Route::group(['prefix' => '/{user}'], function () {
            Route::get('/', [UserController::class, 'show'])->name('show');

            Route::delete('/', [UserController::class, 'destroy'])->name('destroy');

            Route::group(['prefix' => '/show', 'as' => 'show.'], function () {
                Route::group(['prefix' => '/settings', 'as' => 'settings.'], function () {
                    Route::get('/', [Users\Settings\SettingsController::class, 'index'])->name('index');

                    Route::put('/', [Users\Settings\SettingsController::class, 'update'])->name('update');

                });
            });
        });

        Route::get('/search', [UserController::class, 'search'])->name('search');
    });
});
 */