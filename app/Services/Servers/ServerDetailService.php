<?php

namespace App\Services\Servers;

use App\Data\Server\Eloquent\ServerEloquentData;
use App\Data\Server\Proxmox\ServerProxmoxData;
use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxConfigRepository;
use Illuminate\Support\Arr;

class ServerDetailService
{
    public function __construct(private NetworkService $networkService, private ProxmoxConfigRepository $allocationRepository, private AllocationService $allocationService)
    {
    }

    public function getByEloquent(Server $server): ServerEloquentData
    {
        $addresses = $this->networkService->getAddresses($server);

        return ServerEloquentData::from([
            'id' => $server->id,
            'uuid_short' => $server->uuid_short,
            'uuid' => $server->uuid,
            'node_id' => $server->node_id,
            'hostname' => $server->hostname,
            'name' => $server->name,
            'description' => $server->description,
            'status' => $server->status,
            'usages' => [
                'bandwidth' => $server->bandwidth_usage,
            ],
            'limits' => [
                'cpu' => $server->cpu,
                'memory' => $server->memory,
                'disk' => $server->disk,
                'snapshots' => $server->snapshot_limit,
                'backups' => $server->backup_limit,
                'bandwidth' => $server->bandwidth_limit,
                'addresses' => $addresses,
                'mac_address' => $this->networkService->getMacAddresses($server)->eloquent,
            ],
        ]);
    }

    public function getByProxmox(Server $server): ServerProxmoxData
    {
        $server = $server->loadMissing(['addresses', 'node']);

        $resources = $this->allocationRepository->setServer($server)->getResources();

        return ServerProxmoxData::from([
            'id' => $server->id,
            'uuid_short' => $server->uuid_short,
            'uuid' => $server->uuid,
            'node_id' => $server->node_id,
            'state' => Arr::get($resources, 'status'),
            'locked' => Arr::get($resources, 'lock', false),
            'config' => [
                'mac_address' => $this->networkService->getMacAddresses($server, false, true)->proxmox,
                'boot_order' => $this->allocationService->getBootOrder($server),
                'disks' => $this->allocationService->getDisks($server),
                /* 'addresses' => $this->cloudinitService->getIpConfig($server), */
            ],
        ]);
    }
}
