<?php

use Illuminate\Support\Facades\Route;
use Convoy\Http\Controllers\Admin;


Route::resource('locations', Admin\LocationController::class)
    ->only(['index', 'store', 'update', 'destroy']);

Route::resource('nodes', Admin\NodeController::class)
    ->only(['index', 'show', 'store', 'update', 'destroy']);