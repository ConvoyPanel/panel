<?php

use App\Http\Controllers\Application\Users\SecurityController;
use App\Http\Controllers\Application\Users\UserController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => '/users'], function () {
    Route::get('/', [UserController::class, 'index']);

    Route::post('/', [UserController::class, 'store']);

    Route::group(['prefix' => '/{user}'], function () {
        Route::get('/', [UserController::class, 'show']);

        Route::put('/', [UserController::class, 'update']);

        Route::post('/sso', [SecurityController::class, 'store']);
    });
});
