<?php

namespace Convoy\Services\Servers;

use Convoy\Enums\Network\AddressType;
use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Convoy\Models\Objects\Server\ServerDetailsObject;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxAllocationRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxCloudinitRepository;
use Convoy\Services\ProxmoxService;
use Illuminate\Support\Arr;
use Webmozart\Assert\Assert;

class ServerDetailService extends ProxmoxService
{
    public function __construct(protected ProxmoxAllocationRepository $repository, protected ProxmoxCloudinitRepository $cloudinitRepository, protected AllocationService $allocationService, protected CloudinitService $cloudinitService)
    {
    }

    /**
     * @return ServerDetailsObject
     *
     * @throws ProxmoxConnectionException
     */
    public function getDetails()
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
                'uptime' => Arr::get($resources, 'uptime'),
                'network' => [
                    'in' => Arr::get($resources, 'netin'),
                    'out' => Arr::get($resources, 'netout'),
                    'monthly_total' => $this->server->bandwidth_usage,
                ],
                'disk' => [
                    'write' => Arr::get($resources, 'diskwrite'),
                    'read' => Arr::get($resources, 'diskread'),
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
    }
}
