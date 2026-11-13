<?php

use Convoy\Http\Controllers\Base;
use Illuminate\Support\Facades\Route;

Route::get('/', [Base\IndexController::class, 'index'])->name('index')->fallback();

Route::get('/locales/locale.json', Base\LocaleController::class)
    ->where('namespace', '.*');

// Before the catch-all below, which would otherwise answer /up with the SPA and
// a 200 however broken the panel is. Outside the session middleware: the
// healthcheck hits this every 30 seconds, and each hit would start a session
// that lives in Redis for a year.
Route::get('/up', Base\HealthController::class)
    ->withoutMiddleware(['web', 'auth.session'])
    ->name('health');

Route::get('/{any}', [Base\IndexController::class, 'index'])
    ->where('any', '^(?!(\/)?(api|authorize)).+');
