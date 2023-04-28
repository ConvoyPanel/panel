<?php

use Convoy\Http\Controllers\Admin;
use Convoy\Http\Middleware\Admin\Server\ValidateServerStatusMiddleware;
use Illuminate\Support\Facades\Route;

Route::apiResource('locations', Admin\LocationController::class)
    ->only(['index', 'store', 'update', 'destroy']);

Route::apiResource('nodes', Admin\Nodes\NodeController::class)
    ->only(['index', 'show', 'store', 'update', 'destroy']);

Route::prefix('/nodes/{node}')->group(function () {
    Route::apiResource('/isos', Admin\Nodes\IsoController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::apiResource('template-groups', Admin\Nodes\TemplateGroupController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::post('/template-groups/reorder', [Admin\Nodes\TemplateGroupController::class, 'updateOrder']);

    Route::apiResource('template-groups.templates', Admin\Nodes\TemplateController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::post('/template-groups/{template_group}/templates/reorder', [Admin\Nodes\TemplateController::class, 'updateOrder']);

    Route::apiResource('addresses', Admin\Nodes\AddressController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::get('/tools/query-remote-file', [Admin\Nodes\IsoController::class, 'queryLink']);
});

Route::get('/servers', [Admin\ServerController::class, 'index']);
Route::post('/servers', [Admin\ServerController::class, 'store']);
Route::prefix('/servers/{server}')->middleware(ValidateServerStatusMiddleware::class)->group(function () {
    Route::get('/', [Admin\ServerController::class, 'show'])->withoutMiddleware(ValidateServerStatusMiddleware::class);
    Route::patch('/', [Admin\ServerController::class, 'update'])->withoutMiddleware(ValidateServerStatusMiddleware::class);
    Route::delete('/', [Admin\ServerController::class, 'destroy']);

    Route::prefix('/settings')->group(function () {
        Route::patch('/build', [Admin\ServerController::class, 'updateBuild']);

        Route::post('/suspend', [Admin\ServerController::class, 'suspend']);
        Route::post('/unsuspend', [Admin\ServerController::class, 'unsuspend']);
    });
});

Route::apiResource('users', Admin\UserController::class)
    ->only(['index', 'show', 'store', 'update', 'destroy']);

Route::post('/users/{user}/generate-sso-token', [Admin\UserController::class, 'getSSOToken']);
