<?php

namespace App\Http\Controllers\Client\Servers;

use App\Http\Requests\Client\Servers\CreateSnapshotRequest;
use App\Http\Requests\Client\Servers\DeleteSnapshotRequest;
use App\Http\Requests\Client\Servers\RestoreSnapshotRequest;
use App\Jobs\Server\MonitorSnapshotJob;
use App\Models\Server;
use App\Models\Snapshot;
use App\Repositories\Eloquent\SnapshotRepository;
use App\Repositories\Proxmox\Server\ProxmoxSnapshotRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class SnapshotController
{
    public function __construct(
        private SnapshotRepository $repository,
        private ProxmoxSnapshotRepository $proxmoxRepository
    ) {}

    public function index(Server $server): JsonResponse
    {
        return response()->json($this->repository->buildSnapshotTree($server->snapshots));
    }

    public function store(CreateSnapshotRequest $request, Server $server): Response
    {
        DB::transaction(function () use ($request, $server) {
            $data = $request->validated();

            $snapshot = $server->snapshots()->create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'size' => 0,
            ]);

            $task = $this->proxmoxRepository->setServer($server)->create(
                $data['name'],
                'Created by Convoy' . PHP_EOL . PHP_EOL . 'UUID: ' . $snapshot->uuid,
                $data['includes_ram'] ?? false
            );

            MonitorSnapshotJob::dispatch($server, $snapshot, $task);
        });

        return response()->noContent();
    }

    public function restore(RestoreSnapshotRequest $request, Server $server, Snapshot $snapshot): Response
    {
        $this->proxmoxRepository->setServer($server)->restore($snapshot->name);

        return response()->noContent();
    }

    public function destroy(DeleteSnapshotRequest $request, Server $server, Snapshot $snapshot): Response
    {
        $this->proxmoxRepository->setServer($server)->delete($snapshot->name);

        $snapshot->delete();

        return response()->noContent();
    }
}
