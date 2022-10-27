<?php

use Illuminate\Support\Facades\Route;
use Convoy\Http\Controllers\Base;

Route::get('/', [Base\IndexController::class, 'index'])->name('index')->fallback();

Route::get('/{react}', [Base\IndexController::class, 'index'])
    ->where('react', '^(?!(\/)?(api|auth|admin|daemon)).+');