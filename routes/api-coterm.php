<?php

use Illuminate\Support\Facades\Route;
use Convoy\Http\Controllers\Coterm;

Route::prefix('/servers/{server}')->group(function () {
    Route::post('/create-console-session', [Coterm\SessionController::class, 'store']);
});