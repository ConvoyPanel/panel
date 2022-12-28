<?php

use Illuminate\Support\Facades\Route;
use Convoy\Http\Controllers\Base;

Route::get('/', [Base\IndexController::class, 'index'])->name('index')->fallback();

Route::get('/{any}', [Base\IndexController::class, 'index'])
    ->where('any', '^(?!(\/)?(api)).+');
    //->where('any', '^(?!(\/)?(api|auth)).+');