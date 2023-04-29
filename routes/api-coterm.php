<?php

use Illuminate\Support\Facades\Route;
use Convoy\Http\Controllers\Coterm;

Route::prefix('/servers/{server}')->group(function () {
    Route::post('/generate-terminal-session', [Coterm\SessionController::class, 'store']);
});