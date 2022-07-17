<?php

namespace App\Http\Controllers\Client\Servers;

use App\Http\Controllers\ApplicationApiController;
use App\Models\Server;
use App\Services\Servers\CloudinitService;
use App\Services\Servers\SnapshotService;
use App\Http\Requests\Client\Servers\Snapshots\SnapshotRequest;

class SnapshotController extends ApplicationApiController
{
    public function __construct(private SnapshotService $snapshotService, private CloudinitService $cloudinitService)
    {
    }

    public function index(Server $server)
    {
        $data = $this->snapshotService->setServer($server)->fetchSnapshots();
        return inertia('servers/snapshots/Index', [
            'server' => $server,
            'snapshots' => array_reverse($data ? $data['data']: []),
        ]);
    }

    public function create(Server $server, SnapshotRequest $request)
    {
        $this->snapshotService->setServer($server)->doSnapshot($request->name);

        return $this->returnInertiaResponse($request, 'snapshot-created');
    }

    public function delete(Server $server, SnapshotRequest $request)
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