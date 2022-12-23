<?php

use Illuminate\Support\Facades\Route;
use Convoy\Http\Controllers\Admin;


Route::resource('locations', Admin\LocationController::class)
    ->only(['index', 'store', 'update', 'destroy']);

Route::resource('nodes', Admin\Nodes\NodeController::class)
    ->only(['index', 'show', 'store', 'update', 'destroy']);

Route::resource('nodes/{node}/isos', Admin\Nodes\IsoController::class)
    ->only(['index', 'store', 'update', 'destroy']);

Route::resource('servers', Admin\ServerController::class)
    ->only(['index', 'show', 'store', 'update', 'destroy']);

Route::resource('users', Admin\UserController::class)
    ->only(['index', 'show', 'store', 'update', 'destroy']);
