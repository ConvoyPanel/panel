<?php

namespace Convoy\Http\Controllers\Client\Servers;

use Convoy\Http\Controllers\ApiController;
use Convoy\Http\Requests\Client\Servers\Snapshots\SnapshotRequest;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxSnapshotRepository;
use Convoy\Services\Servers\Snapshots\SnapshotCreationService;
use Convoy\Services\Servers\Snapshots\SnapshotDeletionService;

class SnapshotController extends ApiController
{
    public function __construct(
        protected ProxmoxSnapshotRepository $repository,
        protected SnapshotCreationService   $creationService,
        protected SnapshotDeletionService   $deletionService,
    )
    {
    }

    public function index(Server $server)
    {
        $snapshots = $this->repository->setServer($server)->getSnapshots();

        return inertia('servers/snapshots/Index', [
            'server' => $server,
            'snapshots' => $snapshots,
            'can_create' => isset($server->snapshot_limit) ? (count(
                        $snapshots,
                    ) - 1) < $server->snapshot_limit : true,
        ]);
    }

    public function store(Server $server, SnapshotRequest $request)
    {
        $this->creationService->setServer($server)->handle($request->name);

        return back();
    }

    public function destroy(Server $server, SnapshotRequest $request)
    {
        $this->deletionService->setServer($server)->handle($request->name);

        return back();
    }

    public function rollback(Server $server, SnapshotRequest $request)
    {
        $this->repository->setServer($server)->restore($request->name);

        return back();
    }
}
