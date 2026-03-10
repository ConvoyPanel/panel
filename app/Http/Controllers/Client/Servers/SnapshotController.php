<?php

namespace App\Http\Controllers\Client\Servers;

use App\Http\Requests\Client\Servers\StoreSnapshotRequest;
use App\Http\Requests\Client\Servers\UpdateSnapshotRequest;
use App\Http\Requests\Client\Servers\DeleteSnapshotRequest;
use App\Http\Requests\Client\Servers\RestoreSnapshotRequest;
use App\Models\Server;
use App\Models\Snapshot;
use App\Repositories\Proxmox\Server\ProxmoxSnapshotRepository;
use App\Services\Servers\SnapshotService;
use App\Transformers\Client\SnapshotTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class SnapshotController
{
    public function __construct(
        private SnapshotService $service,
        private ProxmoxSnapshotRepository $proxmoxRepository
    ) {}

    public function index(Server $server): JsonResponse
    {
        $data = $this->service->getSnapshotTree($server);

        return fractal($data['snapshot'], new SnapshotTransformer)
            ->addMeta(['current_snapshot_uuid' => $data['current_snapshot_uuid']])
            ->respond();
    }

    public function store(StoreSnapshotRequest $request, Server $server): Response
    {
        $this->service->createSnapshot($server, $request->validated());

        return response()->noContent();
    }

    public function update(UpdateSnapshotRequest $request, Server $server, Snapshot $snapshot): Response
    {
        $this->service->updateSnapshot($server, $snapshot, $request->validated());

        return response()->noContent();
    }

    public function restore(RestoreSnapshotRequest $request, Server $server, Snapshot $snapshot): Response
    {
        $this->proxmoxRepository->setServer($server)->restore($snapshot->name);

        return response()->noContent();
    }

    public function destroy(DeleteSnapshotRequest $request, Server $server, Snapshot $snapshot): Response
    {
        $this->service->deleteSnapshot($server, $snapshot);

        return response()->noContent();
    }
}
