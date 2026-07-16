<?php

use App\Http\Controllers\Base;
use Illuminate\Support\Facades\Route;

Route::get('/', [Base\IndexController::class, 'index'])->name('index')
    ->fallback();

Route::get('/{any}', [Base\IndexController::class, 'index'])
    ->where('any', '^(?!(\/)?(api|authorize)).+');
