<?php

namespace App\Http\Controllers\Client\Servers;

use App\Http\Controllers\ApplicationApiController;
use App\Models\Server;
use App\Services\Servers\CloudinitService;
use App\Services\Servers\SnapshotService;
use App\Http\Requests\Servers\Snapshots\SnapshotRequest;

class SnapshotController extends ApplicationApiController
{
    public function __construct(private SnapshotService $snapshotService, private CloudinitService $cloudinitService)
    {
    }

    public function index(Server $server)
    {
        $data = $this->snapshotService->setServer(clone $server)->fetchSnapshots();

        // use code below if clone is not in the line above
        /* $filteredData = $server->toArray();
        unset($filteredData['node']); */

        return inertia('servers/snapshots/Index', [
            'server' => $server,
            'snapshots' => $data ? $data['data']: [],
        ]);
    }

    public function store(Server $server, SnapshotRequest $request)
    {
        $this->snapshotService->setServer($server)->doSnapshot($request->name);

        return $this->returnInertiaResponse($request, 'snapshot-created');
    }

    public function destroy(Server $server, SnapshotRequest $request)
    {
        $this->snapshotService->setServer($server)->deleteSnapshot($request->name);

        return $this->returnNoContent();
    }

    public function rollback(Server $server, SnapshotRequest $request)
    {
        $this->snapshotService->setServer($server)->rollbackSnapshot($request->name);

        return $this->returnNoContent();
    }
}