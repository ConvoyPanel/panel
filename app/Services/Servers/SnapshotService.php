<?php

namespace App\Services\Servers;

use App\Models\Server;
use App\Models\Snapshot;
use App\Repositories\Eloquent\SnapshotRepository;
use App\Repositories\Proxmox\Server\ProxmoxSnapshotRepository;
use App\Jobs\Server\MonitorSnapshotJob;
use Illuminate\Support\Facades\DB;

class SnapshotService
{
    public function __construct(
        private SnapshotRepository $repository,
        private ProxmoxSnapshotRepository $proxmoxRepository
    ) {}

    /**
     * Get the snapshot tree for a server along with the current snapshot UUID.
     *
     * @param  \App\Models\Server  $server
     * @return array{snapshot: \App\Models\Snapshot|null, current_snapshot_uuid: string|null}
     */
    public function getSnapshotTree(Server $server): array
    {
        $snapshot = $this->repository->buildSnapshotTree($server->snapshots);

        $proxmoxSnapshots = $this->proxmoxRepository->setServer($server)->getSnapshots();
        $parentName = $proxmoxSnapshots->firstWhere('name', 'current')?->parentName;

        $currentSnapshotUuid = null;
        if (filled($parentName)) {
            $currentSnapshotUuid = $server->snapshots->firstWhere('name', $parentName)?->uuid;
        }

        return [
            'snapshot' => $snapshot,
            'current_snapshot_uuid' => $currentSnapshotUuid,
        ];
    }

    /**
     * Create a new snapshot for a server.
     *
     * @param  \App\Models\Server  $server
     * @param  array  $data
     * @return \App\Models\Snapshot
     */
    public function createSnapshot(Server $server, array $data): Snapshot
    {
        $proxmoxSnapshots = $this->proxmoxRepository->setServer($server)->getSnapshots();
        $parentName = $proxmoxSnapshots->firstWhere('name', 'current')?->parentName;

        $parentSnapshotId = null;
        if (filled($parentName)) {
            $parentSnapshotId = $server->snapshots()->where('name', $parentName)->first()?->id;
        }

        return DB::transaction(function () use ($server, $data, $parentSnapshotId) {
            /** @var Snapshot $snapshot */
            $snapshot = $server->snapshots()->create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'snapshot_id' => $parentSnapshotId,
                'size' => 0,
            ]);

            $task = $this->proxmoxRepository->setServer($server)->create(
                $data['name'],
                'Created by Convoy' . PHP_EOL . PHP_EOL . 'UUID: ' . $snapshot->uuid,
                $data['includes_ram'] ?? false
            );

            MonitorSnapshotJob::dispatch($server, $snapshot, $task);

            return $snapshot;
        });
    }

    /**
     * Update a snapshot for a server.
     *
     * @param  \App\Models\Server  $server
     * @param  \App\Models\Snapshot  $snapshot
     * @param  array  $data
     * @return \App\Models\Snapshot
     */
    public function updateSnapshot(Server $server, Snapshot $snapshot, array $data): Snapshot
    {
        return DB::transaction(function () use ($snapshot, $data) {
            $snapshot->update([
                'description' => $data['description'] ?? null,
            ]);

            return $snapshot;
        });
    }

    /**
     * Delete a snapshot for a server.
     *
     * @param  \App\Models\Server  $server
     * @param  \App\Models\Snapshot  $snapshot
     * @return void
     */
    public function deleteSnapshot(Server $server, Snapshot $snapshot): void
    {
        DB::transaction(function () use ($server, $snapshot) {
            $snapshot->children()->update(['snapshot_id' => $snapshot->snapshot_id]);

            $this->proxmoxRepository->setServer($server)->delete($snapshot->name);

            $snapshot->delete();
        });
    }
}
