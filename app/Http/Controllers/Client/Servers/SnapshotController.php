<?php

namespace App\Http\Controllers\Client\Servers;

use App\Http\Controllers\ApplicationApiController;
use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxSnapshotRepository;
use App\Services\Servers\CloudinitService;
use App\Services\Servers\SnapshotService;
use App\Http\Requests\Client\Servers\Snapshots\SnapshotRequest;

class SnapshotController extends ApplicationApiController
{
    public function __construct(private ProxmoxSnapshotRepository $repository)
    {
    }

    public function index(Server $server)
    {
        return inertia('servers/snapshots/Index', [
            'server' => $server,
            'snapshots' => $this->repository->setServer($server)->getSnapshots(),
        ]);
    }


    public function store(Server $server, SnapshotRequest $request)
    {
        $this->repository->setServer($server)->create($request->name);

        return back();
    }

    public function destroy(Server $server, SnapshotRequest $request)
    {
        $this->repository->setServer($server)->delete($request->name);

        return back();
    }

    public function rollback(Server $server, SnapshotRequest $request)
    {
        $this->repository->setServer($server)->restore($request->name);

        return back();
    }
}