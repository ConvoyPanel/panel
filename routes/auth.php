<?php

use Convoy\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /auth
|
*/

Route::get('/authenticate', [LoginController::class, 'authorizeToken']);
