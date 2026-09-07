<?php

use App\Http\Controllers\Admin;
use App\Http\Middleware\Admin\Server\ValidateServerLifecycleMiddleware;
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
| Audit Log Route
|--------------------------------------------------------------------------
|
| Endpoint: /api/admin/audit-logs
|
| The panel-wide audit feed. Filterable by event, area prefix, actor, subject
| and date range; see App\Http\Controllers\Admin\AuditLogController.
|
*/
Route::get('/audit-logs', Admin\AuditLogController::class);

/*
|--------------------------------------------------------------------------
| Version Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/admin/version
|
| What this panel is running and whether a newer release exists. The read is
| served from the last scheduled check; `check` is the manual refresh, and is
| throttled because the limit that matters is GitHub's.
|
*/
Route::get('/version', [Admin\VersionController::class, 'show']);
Route::post('/version/check', [Admin\VersionController::class, 'check'])
    ->middleware('throttle:10,1');

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
// Storage across every node. Node-scoped storage lives under /nodes/{node}/storages;
// this is the inventory, for the questions that are not about one host.
Route::get('/storages', Admin\StorageInventoryController::class);
Route::get('/storages/{storage}/consumers', Admin\StorageConsumerController::class);
// The only backup delete was the client's, scoped to a server. An operator
// clearing a full disk should not have to find the owning server first.
Route::delete('/backups/{backup}', [Admin\StorageBackupController::class, 'destroy']);

Route::post('/clusters/{cluster}/unflag', [Admin\ClusterController::class, 'unflag']);

Route::prefix('/nodes')->group(function () {
    Route::get('/', [Admin\Nodes\NodeController::class, 'index']);
    Route::post('/test-connection', Admin\Nodes\NodeConnectionTestController::class);

    Route::prefix('/{node}')->group(function () {
        Route::get('/', [Admin\Nodes\NodeController::class, 'show']);
        Route::get('/status', Admin\Nodes\NodeStatusController::class);
        Route::post('/test-connection', Admin\Nodes\NodeConnectionTestController::class);
        Route::put('/', [Admin\Nodes\NodeController::class, 'update']);
        Route::delete('/', [Admin\Nodes\NodeController::class, 'destroy']);
        // Re-keys the agent on this host. The node is the installation, so this
        // hangs off the node rather than off a record of its own.
        Route::post('/agent/enrollment', [Admin\Nodes\NodeController::class, 'agentEnrollment']);

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

            /*
             * Endpoint: /api/admin/nodes/{node}/network-interfaces/{network_interface}/vlans
             *
             * A VLAN is only meaningful within its bridge, so it is nested
             * rather than addressed globally — `scopeBindings()` on the group
             * then keeps a VLAN from being reached through another interface.
             */
            Route::prefix('/{network_interface}/vlans')->group(function () {
                Route::get('/', [Admin\Nodes\VlanController::class, 'index']);
                Route::post('/', [Admin\Nodes\VlanController::class, 'store']);
                Route::put('/{vlan}', [Admin\Nodes\VlanController::class, 'update']);
                Route::delete('/{vlan}', [Admin\Nodes\VlanController::class, 'destroy']);
            });
        });

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
    });
});

/*
|--------------------------------------------------------------------------
| Server Preset Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/admin/server-presets
|
| Sits beside /servers rather than under it: a preset is not a server, and
| nesting it would have to share the `{server}` binding it has nothing to do
| with.
|
*/
Route::prefix('/server-presets')->group(function () {
    Route::get('/', [Admin\ServerPresetController::class, 'index']);
    Route::post('/', [Admin\ServerPresetController::class, 'store']);

    Route::prefix('/{server_preset}')->group(function () {
        Route::get('/', [Admin\ServerPresetController::class, 'show']);
        Route::put('/', [Admin\ServerPresetController::class, 'update']);
        Route::delete('/', [Admin\ServerPresetController::class, 'destroy']);
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
        ->middleware(ValidateServerLifecycleMiddleware::class)
        ->group(function () {
            Route::get('/', [Admin\ServerController::class, 'show'])
                ->withoutMiddleware(
                    ValidateServerLifecycleMiddleware::class,
                );
            Route::patch('/', [Admin\ServerController::class, 'update'])
                ->withoutMiddleware(
                    ValidateServerLifecycleMiddleware::class,
                );
            Route::delete('/', [Admin\ServerController::class, 'destroy']);

            // GET /state reads the guest's live condition; POST /power sends a command. They
            // were the same path with opposite meanings, which is what made `{state: 'start'}`
            // look reasonable.
            Route::get('/state', [Admin\ServerController::class, 'getState']);
            Route::post('/power', [Admin\ServerController::class, 'sendPowerCommand']);

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
                Route::post(
                    '/unflag',
                    [Admin\ServerController::class, 'unflag'],
                );
            });
        }
        );
});

Route::prefix('/address-block-groups')->group(function () {
    Route::get('/', [Admin\AddressBlockGroupController::class, 'index']);
    Route::get('/summary', [Admin\AddressBlockGroupController::class, 'summary']);
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
            Route::post('/addresses/bulk', [Admin\AddressController::class, 'bulk']);
            Route::patch('/addresses/{address}', [Admin\AddressController::class, 'update']);
            Route::post('/addresses/{address}/reserve', [Admin\AddressController::class, 'reserve']);
            Route::delete('/addresses/{address}/reserve', [Admin\AddressController::class, 'unreserve']);
            Route::delete('/addresses/{address}', [Admin\AddressController::class, 'destroy']);
        });
    });
});

/*
|--------------------------------------------------------------------------
| ISO Library Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/admin/isos
|
| Panel-wide rather than per node. An ISO is a name, a source and a hash; which
| nodes hold a copy is settled when someone mounts it, not when it is added.
|
*/
Route::get('/isos/query-remote-file', [Admin\ISOs\ISOController::class, 'queryLink']);
Route::post('/isos/uploads', [Admin\ISOs\ISOUploadController::class, 'store']);
Route::resource('/isos', Admin\ISOs\ISOController::class)
    ->only(['index', 'store', 'show', 'update', 'destroy']);

/*
|--------------------------------------------------------------------------
| Image Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/admin/image-groups
|
| Three levels, because the bytes and the settings have different lifecycles: a
| group is how the OS picker is organised, a definition is the hardware profile
| and never changes when an image is rebuilt, and a version is one build's disks.
|
*/
Route::prefix('/images')->group(function () {
    // What Proxmox itself accepts, for the hardware form to build itself from.
    Route::get('/schema', Admin\Images\ImageSchemaController::class);
    Route::post('/uploads', [Admin\Images\ImageUploadController::class, 'store']);
});

Route::prefix('/image-groups')->group(function () {
    Route::get('/', [Admin\Images\ImageGroupController::class, 'index']);
    Route::post('/', [Admin\Images\ImageGroupController::class, 'store']);

    Route::prefix('/{image_group}')->group(function () {
        Route::get('/', [Admin\Images\ImageGroupController::class, 'show']);
        Route::put('/', [Admin\Images\ImageGroupController::class, 'update']);
        Route::delete('/', [Admin\Images\ImageGroupController::class, 'destroy']);

        Route::resource('/images', Admin\Images\ImageDefinitionController::class)
            ->parameters(['images' => 'image_definition'])
            ->only(['index', 'store', 'show', 'update', 'destroy']);

        Route::resource(
            '/images.versions',
            Admin\Images\ImageVersionController::class,
        )
            ->parameters(['images' => 'image_definition', 'versions' => 'image_version'])
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
 * One account's credentials, from the admin side: list them, and take them away.
 *
 * Every nested id is scope-bound to `{user}` (the group's `scopeBindings()`), so a key belonging
 * to someone else 404s without the controller checking. `apiKey` scopes through the `apiKeys`
 * relation, which excludes application tokens — see App\Models\User::apiKeys().
 */
Route::prefix('/users/{user}')->group(function () {
    Route::get('/api-keys', [Admin\UserCredentialController::class, 'apiKeys']);
    Route::delete('/api-keys/{apiKey}', [Admin\UserCredentialController::class, 'destroyApiKey']);

    Route::get('/ssh-keys', [Admin\UserCredentialController::class, 'sshKeys']);
    Route::delete('/ssh-keys/{sshKey}', [Admin\UserCredentialController::class, 'destroySshKey']);

    Route::get('/passkeys', [Admin\UserCredentialController::class, 'passkeys']);
    Route::delete('/passkeys/{passkey}', [Admin\UserCredentialController::class, 'destroyPasskey']);

    Route::get('/oauth-connections', [Admin\UserCredentialController::class, 'oauthConnections']);
    Route::delete(
        '/oauth-connections/{oauthConnection}',
        [Admin\UserCredentialController::class, 'destroyOauthConnection'],
    );

    Route::delete('/two-factor', [Admin\UserCredentialController::class, 'destroyTwoFactor']);
});

/*
|--------------------------------------------------------------------------
| Anchor Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/admin/anchors
|
*/

Route::prefix('/anchors')->group(function () {
    /*
     * Declared before any /{id} group: registered after one, "enrollment-keys"
     * would be matched as a route key and 404 on binding.
     */
    Route::prefix('/enrollment-keys')->group(function () {
        Route::get('/', [Admin\AnchorEnrollmentKeyController::class, 'index']);
        Route::post('/', [Admin\AnchorEnrollmentKeyController::class, 'store']);
        Route::post('/{enrollment_key}/revoke', [Admin\AnchorEnrollmentKeyController::class, 'revoke']);
        Route::delete('/{enrollment_key}', [Admin\AnchorEnrollmentKeyController::class, 'destroy']);
    });

    /*
     * Machines that have introduced themselves and are waiting to be let in.
     * Approving one is what creates a node; there is no other supported way.
     */
    Route::prefix('/enrollments')->group(function () {
        Route::get('/', [Admin\AnchorEnrollmentController::class, 'index']);
        Route::get('/{anchor_enrollment}', [Admin\AnchorEnrollmentController::class, 'show']);
        Route::post('/{anchor_enrollment}/approve', [Admin\AnchorEnrollmentController::class, 'approve']);
        Route::delete('/{anchor_enrollment}', [Admin\AnchorEnrollmentController::class, 'destroy']);
    });
});

/*
|--------------------------------------------------------------------------
| Relay Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/admin/relays
|
| The part of an Anchor deployment that is not a node: a shared public endpoint
| console sessions are routed through. An agent is not addressable here — it is
| the node it runs on.
|
*/
Route::prefix('/relays')->group(function () {
    Route::get('/', [Admin\RelayController::class, 'index']);
    Route::post('/', [Admin\RelayController::class, 'store']);

    Route::prefix('/{relay}')->group(function () {
        Route::get('/', [Admin\RelayController::class, 'show']);
        Route::put('/', [Admin\RelayController::class, 'update']);
        Route::delete('/', [Admin\RelayController::class, 'destroy']);
        Route::post('/enrollment', [Admin\RelayController::class, 'enrollment']);
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

    Route::get(
        '/anchor',
        [Admin\Settings\AnchorSettingsController::class, 'show'],
    );
    Route::put(
        '/anchor',
        [Admin\Settings\AnchorSettingsController::class, 'update'],
    );

    Route::get(
        '/account',
        [Admin\Settings\AccountSettingsController::class, 'show'],
    );
    Route::put(
        '/account',
        [Admin\Settings\AccountSettingsController::class, 'update'],
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
