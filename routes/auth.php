<?php

use Convoy\Http\Controllers\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /auth
|
*/

Route::get('/auth/login', [Auth\LoginController::class, 'index']);

Route::get('/sso/authorize', [Auth\LoginController::class, 'authorizeToken']);