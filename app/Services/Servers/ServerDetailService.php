<?php

namespace Convoy\Services\Servers;

use Convoy\Data\Server\Eloquent\ServerEloquentData;
use Convoy\Enums\Network\AddressType;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxAllocationRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxCloudinitRepository;

class ServerDetailService
{
    public function __construct(protected ProxmoxAllocationRepository $repository, protected ProxmoxCloudinitRepository $cloudinitRepository, protected AllocationService $allocationService, protected CloudinitService $cloudinitService)
    {
    }

    public function getByEloquent(Server $server)
    {
        $server = $server->loadMissing('addresses');

        return ServerEloquentData::from([
            'id' => $server->id,
            'uuid_short' => $server->uuid_short,
            'uuid' => $server->uuid,
            'node_id' => $server->node_id,
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
                'addresses' => [
                    'ipv4' => $server->addresses->where('type', AddressType::IPV4->value)->toArray(),
                    'ipv6' => $server->addresses->where('type', AddressType::IPV6->value)->toArray()
                ],
            ]
        ]);
    }

    /* public function getDetails()
    {
        Assert::isInstanceOf($this->server, Server::class);

        $this->repository->setServer($this->server);
        $this->allocationService->setServer($this->server);
        $this->cloudinitService->setServer($this->server);
        $this->cloudinitRepository->setServer($this->server);

        $resources = $this->repository->getResources();
        $config = $this->cloudinitRepository->getConfig();

        $addresses = [
            'ipv4' => $this->server->addresses()->where('type', AddressType::IPV4->value)->get(['address', 'cidr', 'gateway', 'mac_address'])?->toArray(),
            'ipv6' => $this->server->addresses()->where('type', AddressType::IPV6->value)->get(['address', 'cidr', 'gateway', 'mac_address'])?->toArray(),
        ];

        $mac_address = null;
        if (preg_match("/\b[[:xdigit:]]{2}:[[:xdigit:]]{2}:[[:xdigit:]]{2}:[[:xdigit:]]{2}:[[:xdigit:]]{2}:[[:xdigit:]]{2}\b/su", Arr::get($config, 'net0', ''), $matches)) {
            $mac_address = $matches[0];
        }

        $details = [
            'vmid' => $this->server->vmid,
            'status' => $this->server->status ?? Arr::get($resources, 'status'),
            'locked' => Arr::get($resources, 'lock', false),
            'usage' => [
                'uptime' => Arr::get($resources, 'uptime', 0),
                'network' => [
                    'in' => Arr::get($resources, 'netin', 0),
                    'out' => Arr::get($resources, 'netout', 0),
                    'monthly_total' => $this->server->bandwidth_usage,
                ],
                'disk' => [
                    'write' => Arr::get($resources, 'diskwrite', 0),
                    'read' => Arr::get($resources, 'diskread', 0),
                ],
            ],
            'limits' => [
                'cpu' => $this->server->cpu,
                'memory' => $this->server->memory,
                'disk' => $this->server->disk,
                'addresses' => $addresses,
                'snapshot_limit' => $this->server->snapshot_limit,
                'backup_limit' => $this->server->backup_limit,
                'bandwidth_limit' => $this->server->bandwidth_limit,
                'mac_address' => Arr::first($addresses['ipv4'], default: null)?->mac_address ?? Arr::first($addresses['ipv6'], default: null)?->mac_address,
            ],
            'config' => [
                'mac_address' => $mac_address,
                'boot_order' => $this->allocationService->getBootOrder(),
                'disks' => $this->allocationService->getDisks(),
                'template' => Arr::get($resources, 'template'),
                'addresses' => $this->cloudinitService->getIpConfig(),
            ],
            'node_id' => $this->server->node->id,
        ];

        return ServerDetailsObject::from($details);
    } */
}
