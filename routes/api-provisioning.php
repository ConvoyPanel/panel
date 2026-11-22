<?php

use App\Http\Controllers\Provisioning\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Provisioning Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/provisioning
|
| Called from inside a guest, by the runner its provisioner snippet installs.
| There is no account behind the request: the bearer token is one the panel
| minted for that run and put in the snippet, and it is good for one report.
|
*/
Route::post('/report', ReportController::class)
    ->middleware('throttle:30,1')
    ->name('report');
