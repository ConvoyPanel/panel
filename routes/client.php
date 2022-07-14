<?php

use App\Http\Controllers\Client\IndexController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [IndexController::class, 'index'])->name('dashboard');