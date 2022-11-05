<?php

use Convoy\Http\Controllers\Client;
use Convoy\Http\Middleware\Activity\ServerSubject;
use Convoy\Http\Middleware\Client\Server\AuthenticateServerAccess;
use Illuminate\Support\Facades\Route;

Route::get('/servers', [Client\IndexController::class, 'index']);

Route::group([
    'prefix' => '/servers/{server}',
    'middleware' => [
        ServerSubject::class,
        AuthenticateServerAccess::class,
    ],
], function () {
    Route::get('/', [Client\Servers\ServerController::class, 'index']);
});