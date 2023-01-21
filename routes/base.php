<?php

use Illuminate\Support\Facades\Route;
use Convoy\Http\Controllers\Base;
use Convoy\Http\Controllers\Auth\LoginController;

Route::get('/', [Base\IndexController::class, 'index'])->name('index')->fallback();

Route::get('/{any}', [Base\IndexController::class, 'index'])
    ->where('any', '^(?!(\/)?(api|authorize)).+');
    //->where('any', '^(?!(\/)?(api|auth)).+');