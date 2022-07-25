<?php

use App\Http\Controllers\Admin\IndexController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => '/admin', 'as' => 'admin.'], function () {
    Route::get('/', [IndexController::class, 'index'])->name('dashboard');
});
