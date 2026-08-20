<?php

namespace App\Http\Controllers\Admin;

use App\Data\Server\ServerDiskData;
use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Http\Requests\Admin\Servers\Disks\AddServerDiskRequest;
use App\Http\Requests\Admin\Servers\Disks\ResizeServerDiskRequest;
use App\Models\Server;
use App\Models\ServerDisk;
use App\Services\Servers\AllocationService;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class ServerDiskController
{
    public function __construct(
        private AllocationService $allocationService,
    ) {}

    /**
     * List a server's disks (primary + secondaries).
     */
    public function index(Server $server)
    {
        return ServerDiskData::collect($this->disks($server));
    }

    /**
     * Add a secondary data disk. Sizes are in bytes (validated against the
     * target storage's free-for-Convoy space by the request).
     */
    public function store(AddServerDiskRequest $request, Server $server)
    {
        $disk = $this->allocationService->addDisk(
            $server,
            (int) $request->input('storage_id'),
            (int) $request->input('size'),
        );

        Audit::record(
            AuditEvent::ADMIN_SERVER_DISK_CREATED,
            subject: $server,
            properties: ['size' => $disk->size, 'storage_id' => $disk->storage_id],
        );

        return ServerDiskData::from($disk->load('storage'));
    }

    /**
     * Grow a secondary disk (shrink is rejected by the service).
     */
    public function update(ResizeServerDiskRequest $request, Server $server, ServerDisk $disk)
    {
        $previousSize = $disk->size;

        $this->allocationService->resizeDisk($server, $disk, (int) $request->input('size'));

        Audit::record(
            AuditEvent::ADMIN_SERVER_DISK_UPDATED,
            subject: $server,
            properties: ['from' => $previousSize, 'to' => (int) $request->input('size')],
        );

        return ServerDiskData::from($disk->refresh()->load('storage'));
    }

    /**
     * Remove a secondary disk and reclaim its space on Proxmox.
     */
    public function destroy(Server $server, ServerDisk $disk): Response
    {
        $properties = ['size' => $disk->size, 'disk_index' => $disk->disk_index];

        $this->allocationService->removeDisk($server, $disk);

        Audit::record(
            AuditEvent::ADMIN_SERVER_DISK_DELETED,
            subject: $server,
            properties: $properties,
        );

        return response()->noContent();
    }

    /**
     * @return Collection<int, ServerDisk>
     */
    private function disks(Server $server): Collection
    {
        return $server->disks()->with('storage')->orderBy('disk_index')->get();
    }
}
