<?php

use App\Http\Controllers\Admin;
use App\Http\Middleware\Admin\Server\ValidateServerStatusMiddleware;
use App\Http\Middleware\DenyApiTokenAccess;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Overview Controller Route
|--------------------------------------------------------------------------
|
| Endpoint: /api/admin/overview
|
*/
Route::get('/overview', Admin\OverviewController::class);

/*
|--------------------------------------------------------------------------
| Location Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/admin/locations
|
*/
Route::prefix('/locations')->group(function () {
    Route::get('/', [Admin\LocationController::class, 'index']);
    Route::post('/', [Admin\LocationController::class, 'store']);

    Route::prefix('/{location}')->group(function () {
        Route::get('/', [Admin\LocationController::class, 'show']);
        Route::get('/nodes', [Admin\LocationController::class, 'showAttachedNodes']);
        Route::put('/', [Admin\LocationController::class, 'update']);
        Route::delete('/', [Admin\LocationController::class, 'destroy']);
    });
});

/*
|--------------------------------------------------------------------------
| Node Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/admin/nodes
|
*/
Route::prefix('/nodes')->group(function () {
    Route::get('/', [Admin\Nodes\NodeController::class, 'index']);
    Route::post('/', [Admin\Nodes\NodeController::class, 'store']);
    Route::post('/test-connection', Admin\Nodes\NodeConnectionTestController::class);

    Route::prefix('/{node}')->group(function () {
        Route::get('/', [Admin\Nodes\NodeController::class, 'show']);
        Route::get('/status', Admin\Nodes\NodeStatusController::class);
        Route::post('/test-connection', Admin\Nodes\NodeConnectionTestController::class);
        Route::put('/', [Admin\Nodes\NodeController::class, 'update']);
        Route::delete('/', [Admin\Nodes\NodeController::class, 'destroy']);

        Route::prefix('/storages')->group(function () {
            Route::get('/', [Admin\Nodes\StorageController::class, 'index']);
            Route::get('/proxmox', [Admin\Nodes\StorageController::class, 'fetchFromProxmox']);
            Route::post('/', [Admin\Nodes\StorageController::class, 'store']);
            Route::put('/backup-order', [Admin\Nodes\StorageController::class, 'updateBackupOrder']);

            Route::put('/{storage}', [Admin\Nodes\StorageController::class, 'update']);
            Route::delete('/{storage}', [Admin\Nodes\StorageController::class, 'destroy']);
        });

        Route::prefix('/network-interfaces')->group(function () {
            Route::get('/', [Admin\Nodes\NetworkInterfaceController::class, 'index']);
            Route::post('/', [Admin\Nodes\NetworkInterfaceController::class, 'store']);
            Route::put('/{network_interface}', [Admin\Nodes\NetworkInterfaceController::class, 'update']);
            Route::delete('/{network_interface}', [Admin\Nodes\NetworkInterfaceController::class, 'destroy']);
        });

        Route::resource('/isos', Admin\Nodes\IsoController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | Node Addresses Controller Routes
        |--------------------------------------------------------------------------
        |
        | Endpoint: /api/admin/nodes/{node}/addresses
        |
        */
        Route::get(
            '/addresses',
            [Admin\Nodes\AddressController::class, 'index'],
        );

        /*
         |--------------------------------------------------------------------------
         | Node Helpers Routes
         |--------------------------------------------------------------------------
         */
        Route::get(
            '/tools/query-remote-file',
            [Admin\Nodes\IsoController::class, 'queryLink'],
        );
    });
});

/*
|--------------------------------------------------------------------------
| Server Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/admin/servers
|
*/
Route::prefix('/servers')->group(function () {
    Route::get('/', [Admin\ServerController::class, 'index']);
    Route::post('/', [Admin\ServerController::class, 'store']);

    Route::prefix('/{server}')
        ->middleware(ValidateServerStatusMiddleware::class)
        ->group(function () {
            Route::get('/', [Admin\ServerController::class, 'show'])
                ->withoutMiddleware(
                    ValidateServerStatusMiddleware::class,
                );
            Route::patch('/', [Admin\ServerController::class, 'update'])
                ->withoutMiddleware(
                    ValidateServerStatusMiddleware::class,
                );
            Route::delete('/', [Admin\ServerController::class, 'destroy']);

            Route::get('/state', [Admin\ServerController::class, 'getState']);
            Route::patch('/state', [Admin\ServerController::class, 'updateState']);

            Route::prefix('/disks')->scopeBindings()->group(function () {
                Route::get('/', [Admin\ServerDiskController::class, 'index']);
                Route::post('/', [Admin\ServerDiskController::class, 'store']);
                Route::patch('/{disk}', [Admin\ServerDiskController::class, 'update']);
                Route::delete('/{disk}', [Admin\ServerDiskController::class, 'destroy']);
            });

            Route::prefix('/settings')->group(function () {
                Route::patch(
                    '/build',
                    [Admin\ServerController::class, 'updateBuild'],
                );

                Route::post(
                    '/suspend',
                    [Admin\ServerController::class, 'suspend'],
                );
                Route::post(
                    '/unsuspend',
                    [Admin\ServerController::class, 'unsuspend'],
                );
            });
        }
        );
});

Route::prefix('/address-block-groups')->group(function () {
    Route::get('/', [Admin\AddressBlockGroupController::class, 'index']);
    Route::post('/', [Admin\AddressBlockGroupController::class, 'store']);

    Route::prefix('/{address_block_group}')->group(function () {
        Route::get('/', [Admin\AddressBlockGroupController::class, 'show']);
        Route::put('/', [Admin\AddressBlockGroupController::class, 'update']);
        Route::delete('/', [Admin\AddressBlockGroupController::class, 'destroy']);
        Route::get('/compatible-servers', [Admin\AddressBlockGroupController::class, 'getCompatibleServers']);
        Route::get('/nodes', [Admin\AddressBlockGroupController::class, 'getAttachedNodes']);
        Route::post('/nodes', [Admin\AddressBlockGroupController::class, 'attachNode']);
        Route::delete('/nodes/{node}', [Admin\AddressBlockGroupController::class, 'detachNode']);

        Route::get('/address-blocks', [Admin\AddressBlockController::class, 'index']);
        Route::post('/address-blocks', [Admin\AddressBlockController::class, 'store']);
        Route::prefix('/address-blocks/{address_block}')->group(function () {
            Route::get('/', [Admin\AddressBlockController::class, 'show']);
            Route::put('/', [Admin\AddressBlockController::class, 'update']);
            Route::delete('/', [Admin\AddressBlockController::class, 'destroy']);

            Route::get('/addresses', [Admin\AddressController::class, 'index']);
            Route::post('/addresses/generate', [Admin\AddressController::class, 'generate']);
            Route::patch('/addresses/{address}', [Admin\AddressController::class, 'update']);
            Route::post('/addresses/{address}/reserve', [Admin\AddressController::class, 'reserve']);
            Route::delete('/addresses/{address}/reserve', [Admin\AddressController::class, 'unreserve']);
            Route::delete('/addresses/{address}', [Admin\AddressController::class, 'destroy']);
        });
    });
});

Route::prefix('/template-groups')->group(function () {
    Route::get('/', [Admin\TemplateGroupController::class, 'index']);
    Route::post('/', [Admin\TemplateGroupController::class, 'store']);

    Route::prefix('/{template_group}')->group(function () {
        Route::get('/', [Admin\TemplateGroupController::class, 'show']);
        Route::put('/', [Admin\TemplateGroupController::class, 'update']);
        Route::delete('/', [Admin\TemplateGroupController::class, 'destroy']);

        Route::resource('/templates', Admin\TemplateController::class)
            ->only(['index', 'store', 'show', 'update', 'destroy']);
    });
});

/*
|--------------------------------------------------------------------------
| User Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/admin/users
|
*/
Route::resource('users', Admin\UserController::class)
    ->only(['index', 'show', 'store', 'update', 'destroy']);
Route::post(
    '/users/{user}/generate-sso-token',
    [Admin\UserController::class, 'getSSOToken'],
);

/*
|--------------------------------------------------------------------------
| Anchor Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/admin/anchors
|
*/

Route::prefix('/anchors')->group(function () {
    Route::get('/', [Admin\AnchorController::class, 'index']);
    Route::post('/', [Admin\AnchorController::class, 'store']);

    Route::prefix('/{anchor}')->group(function () {
        Route::get('/', [Admin\AnchorController::class, 'show']);
        Route::put('/', [Admin\AnchorController::class, 'update']);
        Route::delete('/', [Admin\AnchorController::class, 'destroy']);
        Route::post('/enrollment', [Admin\AnchorController::class, 'enrollment']);
    });
});

/*
|--------------------------------------------------------------------------
| Panel Settings Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/admin/settings/*
|
| Panel-wide defaults, one section per route — the same split the admin
| Settings screen's sidebar sub-nav uses.
|
*/
Route::prefix('/settings')->group(function () {
    Route::get(
        '/bandwidth',
        [Admin\Settings\BandwidthSettingsController::class, 'show'],
    );
    Route::put(
        '/bandwidth',
        [Admin\Settings\BandwidthSettingsController::class, 'update'],
    );
});

/*
|--------------------------------------------------------------------------
| API Token Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/admin/tokens
|
| Session-only: managing the panel-wide API tokens must never be possible
| with an API token itself, so this is gated behind DenyApiTokenAccess even
| though the rest of this file is also served to the token API.
|
*/
Route::resource('tokens', Admin\TokenController::class)
    ->only(['index', 'store', 'update', 'destroy'])
    ->middleware(DenyApiTokenAccess::class);
