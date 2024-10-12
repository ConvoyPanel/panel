<?php

use Convoy\Http\Controllers\Coterm;
use Illuminate\Support\Facades\Route;

Route::prefix('/servers/{server}')->group(function () {
    Route::post(
        '/create-console-session',
        [Coterm\SessionController::class, 'store'],
    );
});
