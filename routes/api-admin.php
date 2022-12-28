<?php

use Illuminate\Support\Facades\Route;
use Convoy\Http\Controllers\Admin;


Route::resource('locations', Admin\LocationController::class)
    ->only(['index', 'store', 'update', 'destroy']);

Route::resource('nodes', Admin\Nodes\NodeController::class)
    ->only(['index', 'show', 'store', 'update', 'destroy']);

Route::prefix('/nodes/{node}')->group(function () {
    Route::resource('/isos', Admin\Nodes\IsoController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::resource('template-groups', Admin\Nodes\TemplateGroupController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::post('/template-groups/reorder', [Admin\Nodes\TemplateGroupController::class, 'updateOrder']);

    Route::resource('template-groups.templates', Admin\Nodes\TemplateController::class)
        ->only(['store', 'update', 'destroy']);
    Route::post('/template-groups/{template_group}/templates/reorder', [Admin\Nodes\TemplateController::class, 'updateOrder']);


    Route::get('/tools/query-remote-file', [Admin\Nodes\IsoController::class, 'queryLink']);
});

Route::resource('servers', Admin\ServerController::class)
    ->only(['index', 'show', 'store', 'update', 'destroy']);

Route::resource('users', Admin\UserController::class)
    ->only(['index', 'show', 'store', 'update', 'destroy']);
