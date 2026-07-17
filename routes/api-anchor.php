<?php

use App\Http\Controllers\Anchor\EnrollmentController;
use App\Http\Controllers\Anchor\HeartbeatController;
use App\Http\Middleware\AnchorAuthenticate;
use Illuminate\Support\Facades\Route;

Route::post('/enroll', EnrollmentController::class)
    ->middleware('throttle:10,1')
    ->name('enroll');
Route::post('/heartbeat', HeartbeatController::class)
    ->middleware(AnchorAuthenticate::class)
    ->name('heartbeat');
