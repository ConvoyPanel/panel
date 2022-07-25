<?php

use App\Http\Controllers\Admin\IndexController;
use App\Http\Controllers\Admin\Nodes\NodeController;
use App\Http\Controllers\Admin\Servers\ServerController;
use App\Http\Controllers\Admin\Users\UserController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => '/admin', 'as' => 'admin.'], function () {
    Route::get('/', [IndexController::class, 'index'])->name('dashboard');

    Route::group(['prefix' => 'nodes', 'as' => 'nodes.'], function () {
        Route::get('/', [NodeController::class, 'index'])->name('index');
    });

    Route::group(['prefix' => 'servers', 'as' => 'servers.'], function () {
        Route::get('/', [ServerController::class, 'index'])->name('index');
    });

    Route::group(['prefix' => 'users', 'as' => 'users.'], function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
    });
});
