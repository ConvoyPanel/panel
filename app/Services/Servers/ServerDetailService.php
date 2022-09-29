<?php

namespace Convoy\Services\Servers;

use Convoy\Enums\Network\AddressType;
use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Convoy\Models\Objects\Server\ServerDetailsObject;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxAllocationRepository;
use Convoy\Services\ProxmoxService;
use Illuminate\Support\Arr;
use Webmozart\Assert\Assert;

class ServerDetailService extends ProxmoxService
{
    public function __construct(protected ProxmoxAllocationRepository $repository, protected AllocationService $allocationService, protected CloudinitService $cloudinitService)
    {
    }

    /**
     * @return ServerDetailsObject
     * @throws ProxmoxConnectionException
     */
    public function getDetails()
    {
        Assert::isInstanceOf($this->server, Server::class);

        $this->repository->setServer($this->server);
        $this->allocationService->setServer($this->server);
        $this->cloudinitService->setServer($this->server);

        //$config = Arr::keyBy($this->repository->getAllocations(), 'key');
        $resources = $this->repository->getResources();

        $details = [
            'vmid' => $this->server->vmid,
            'status' => Arr::get($resources, 'status'),
            'locked' => Arr::get($resources, 'lock', false),
            'usage' => [
                'uptime' => Arr::get($resources, 'uptime'),
                'network' => [
                    'in' => Arr::get($resources, 'netin'),
                    'out' => Arr::get($resources, 'netout'),
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
                'addresses' => [
                    'ipv4' => $this->server->addresses()->where('type', AddressType::IPV4->value)->get(['address', 'cidr', 'gateway', 'mac_address'])?->toArray(),
                    'ipv6' => $this->server->addresses()->where('type', AddressType::IPV6->value)->get(['address', 'cidr', 'gateway', 'mac_address'])?->toArray(),
                ]
            ],
            'config' => [
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
