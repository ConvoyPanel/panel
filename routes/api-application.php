<?php

use App\Http\Controllers\Admin;
use App\Http\Middleware\Admin\Server\ValidateServerStatusMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Location Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/locations
|
*/
Route::prefix('/locations')->group(function () {
    Route::get('/', [Admin\LocationController::class, 'index']);
    Route::post('/', [Admin\LocationController::class, 'store']);

    Route::prefix('/{location}')->group(function () {
        Route::put('/', [Admin\LocationController::class, 'update']);
        Route::delete('/', [Admin\LocationController::class, 'destroy']);
    });
});

/*
|--------------------------------------------------------------------------
| Node Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/nodes
|
*/
Route::prefix('/nodes')->group(function () {
    Route::get('/', [Admin\Nodes\NodeController::class, 'index']);
    Route::post('/', [Admin\Nodes\NodeController::class, 'store']);

    Route::prefix('/{node}')->group(function () {
        Route::get('/', [Admin\Nodes\NodeController::class, 'show']);
        Route::put('/', [Admin\Nodes\NodeController::class, 'update']);
        Route::delete('/', [Admin\Nodes\NodeController::class, 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | Node ISOs Controller Routes
        |--------------------------------------------------------------------------
        |
        | Endpoint: /api/application/nodes/{node}/isos
        |
        */
        Route::apiResource('/isos', Admin\Nodes\IsoController::class)
             ->only(['index', 'store', 'update', 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | Node Templates Controller Routes
        |--------------------------------------------------------------------------
        |
        | Endpoint: /api/application/nodes/{node}/template-groups
        |
        */
        Route::prefix('/template-groups')->group(function () {
            Route::get(
                '/',
                [Admin\Nodes\TemplateGroupController::class, 'index'],
            );
            Route::post(
                '/',
                [Admin\Nodes\TemplateGroupController::class, 'store'],
            );
            Route::post(
                '/reorder',
                [Admin\Nodes\TemplateGroupController::class, 'updateOrder'],
            );

            Route::prefix('/{template_group}')->group(function () {
                Route::put(
                    '/',
                    [Admin\Nodes\TemplateGroupController::class, 'update'],
                );
                Route::delete(
                    '/',
                    [Admin\Nodes\TemplateGroupController::class, 'destroy'],
                );
                Route::prefix('/templates')->group(function () {
                    Route::get(
                        '/',
                        [Admin\Nodes\TemplateController::class, 'index'],
                    );
                    Route::post(
                        '/',
                        [Admin\Nodes\TemplateController::class, 'store'],
                    );
                    Route::post(
                        '/reorder',
                        [Admin\Nodes\TemplateController::class, 'updateOrder'],
                    );

                    Route::get(
                        '/{template}',
                        [Admin\Nodes\TemplateController::class, 'show'],
                    );
                    Route::delete(
                        '/{template}',
                        [Admin\Nodes\TemplateController::class, 'destroy'],
                    );
                });

                Route::apiResource(
                    'templates',
                    Admin\Nodes\TemplateController::class,
                )
                     ->only(['index', 'store', 'update', 'destroy']);
            });
        });

        /*
        |--------------------------------------------------------------------------
        | Node Addresses Controller Routes
        |--------------------------------------------------------------------------
        |
        | Endpoint: /api/application/nodes/{node}/addresses
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
| Endpoint: /api/application/servers
|
*/
Route::prefix('/servers')->group(function () {
    Route::get('/', [Admin\ServerController::class, 'index']);
    Route::post('/', [Admin\ServerController::class, 'store']);

    Route::prefix('/{server}')->middleware(ValidateServerStatusMiddleware::class)->group(function () {
            Route::get('/', [Admin\ServerController::class, 'show'])
                 ->withoutMiddleware(
                     ValidateServerStatusMiddleware::class,
                 );
            Route::patch('/', [Admin\ServerController::class, 'update'])
                 ->withoutMiddleware(
                     ValidateServerStatusMiddleware::class,
                 );
            Route::delete('/', [Admin\ServerController::class, 'destroy']);

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

/*
|--------------------------------------------------------------------------
| Address Pool Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/address-pools
|
*/
Route::prefix('/address-pools')->group(function () {
    Route::get(
        '/',
        [Admin\AddressPools\AddressPoolController::class, 'index'],
    );
    Route::post(
        '/',
        [Admin\AddressPools\AddressPoolController::class, 'store'],
    );

    Route::prefix('/{address_pool}')->group(function () {
        Route::get(
            '/',
            [Admin\AddressPools\AddressPoolController::class, 'show'],
        );
        Route::put(
            '/',
            [Admin\AddressPools\AddressPoolController::class, 'update'],
        );
        Route::delete(
            '/',
            [Admin\AddressPools\AddressPoolController::class, 'destroy'],
        );
        Route::get(
            '/attached-nodes',
            [Admin\AddressPools\AddressPoolController::class, 'getAttachedNodes'],
        );

        Route::prefix('/addresses')->group(function () {
            Route::get(
                '/',
                [Admin\AddressPools\AddressController::class, 'index'],
            );
            Route::post(
                '/',
                [Admin\AddressPools\AddressController::class, 'store'],
            );
            Route::put(
                '/{address}',
                [Admin\AddressPools\AddressController::class, 'update'],
            );
            Route::delete(
                '/{address}',
                [Admin\AddressPools\AddressController::class, 'destroy'],
            );
        });
    });
});

/*
|--------------------------------------------------------------------------
| User Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/users
|
*/

Route::apiResource('users', Admin\UserController::class)
     ->only(['index', 'show', 'store', 'update', 'destroy']);
Route::post(
    '/users/{user}/generate-sso-token',
    [Admin\UserController::class, 'getSSOToken'],
);

/*
|--------------------------------------------------------------------------
| Coterm Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/coterms
|
*/

Route::prefix('/coterms')->group(function () {
    Route::get('/', [Admin\CotermController::class, 'index']);
    Route::post('/', [Admin\CotermController::class, 'store']);

    Route::prefix('/{coterm}')->group(function () {
        Route::get('/', [Admin\CotermController::class, 'show']);
        Route::put('/', [Admin\CotermController::class, 'update']);
        Route::post(
            '/reset-coterm-token',
            [Admin\CotermController::class, 'resetCotermToken'],
        );
        Route::delete('/', [Admin\CotermController::class, 'destroy']);
        Route::get(
            '/nodes',
            [Admin\CotermController::class, 'getAttachedNodes'],
        );
        Route::put(
            '/nodes',
            [Admin\CotermController::class, 'updateAttachedNodes'],
        );
    });
});
