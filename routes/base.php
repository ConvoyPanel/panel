<?php

use App\Http\Controllers\Base;
use Illuminate\Support\Facades\Route;

// Ahead of the SPA catch-all below, which would otherwise answer this with the
// app shell. The controller does its own signed-in check -- the shell is
// reachable signed out (the login screen is client-side), a stored picture is
// not.
Route::get('/avatars/{path}', Base\AvatarController::class)
    ->where('path', '[0-9a-f-]{36}/[0-9a-f]{64}\.webp')
    ->name('avatars.show');

Route::get('/', [Base\IndexController::class, 'index'])->name('index')
    ->fallback();

Route::get('/{any}', [Base\IndexController::class, 'index'])
    ->where('any', '^(?!(\/)?(api|authorize)).+');
