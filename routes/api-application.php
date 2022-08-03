<?php

use App\Http\Controllers\Application\Nodes\Addresses\AddressController;
use App\Http\Controllers\Application\Nodes\NodeController;
use App\Http\Controllers\Application\Servers\ServerController;
use App\Http\Controllers\Application\Users\SecurityController;
use App\Http\Controllers\Application\Users\UserController;
use App\Http\Controllers\Application\Nodes\Templates\TemplateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/users
|
*/

Route::group(['prefix' => '/users'], function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);

    Route::group(['prefix' => '/{user}'], function () {
        Route::get('/', [UserController::class, 'show']);
        Route::put('/', [UserController::class, 'update']);
        Route::delete('/', [UserController::class, 'destroy']);
        Route::post('/sso', [SecurityController::class, 'store']);
    });
});

/*
|--------------------------------------------------------------------------
| Server Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/servers
|
*/

Route::group(['prefix' => '/servers'], function () {
    Route::get('/', [ServerController::class, 'index']);
    Route::post('/', [ServerController::class, 'store']);

    Route::group(['prefix' => '/{server}'], function () {
        Route::get('/', [ServerController::class, 'show']);
        Route::post('/', [ServerController::class, 'store']);
        Route::delete('/', [ServerController::class, 'destroy']);
        Route::get('/specifications', [ServerController::class, 'getSpecifications']);
        Route::patch('/specifications', [ServerController::class, 'updateSpecifications']);
    });
});

/*
|--------------------------------------------------------------------------
| Node Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/servers
|
*/

Route::group(['prefix' => '/nodes'], function () {
    Route::get('/', [NodeController::class, 'index']);

    Route::group(['prefix' => '/{node}'], function () {
        Route::get('/', [NodeController::class, 'show']);
        Route::get('/addresses', [AddressController::class, 'index']);
        Route::get('/templates', [TemplateController::class, 'index']);

        Route::group(['prefix' => '/addresses/{address}'], function () {
            Route::get('/', [AddressController::class, 'show']);
            Route::post('/', [AddressController::class, 'store']);
            Route::delete('/', [AddressController::class, 'destroy']);
            Route::put('/', [AddressController::class, 'update']);
        });

        Route::group(['prefix' => '/templates/{template}'], function () {
            Route::get('/', [TemplateController::class, 'show']);
        });
    });
});