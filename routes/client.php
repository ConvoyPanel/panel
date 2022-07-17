<?php

use App\Http\Controllers\Client\IndexController;
use App\Http\Controllers\Client\Servers\ServerController;
use App\Http\Controllers\Client\Servers\StatusController;
use App\Http\Middleware\AuthenticateServerAccess;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [IndexController::class, 'index'])->name('dashboard');

Route::group(['prefix' => '/servers/{server}', 'middleware' => AuthenticateServerAccess::class, 'as' => 'servers.'], function () {
    Route::get('/', [ServerController::class, 'show'])->name('show');

    Route::group(['as' => 'show.'], function () {
        Route::get('/status', [StatusController::class, 'show'])->name('status');
    });
});