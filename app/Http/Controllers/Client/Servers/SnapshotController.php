<?php

namespace Convoy\Http\Controllers\Client\Servers;

use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxSnapshotRepository;
use Convoy\Services\Servers\CloudinitService;
use Convoy\Services\Servers\SnapshotService;
use Convoy\Http\Requests\Client\Servers\Snapshots\SnapshotRequest;

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