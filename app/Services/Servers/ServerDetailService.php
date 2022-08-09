<?php

namespace App\Services\Servers;

use App\Enums\Network\AddressType;
use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxAllocationRepository;
use App\Services\ProxmoxService;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Support\Arr;
use Webmozart\Assert\Assert;

class ServerDetailService extends ProxmoxService
{
    private ProxmoxAllocationRepository $repository;
    private AllocationService $allocationService;
    private CloudinitService $cloudinitService;

    public function __construct()
    {
        $this->repository = new ProxmoxAllocationRepository;
        $this->allocationService = new AllocationService;
        $this->cloudinitService  = new CloudinitService;
    }

    public function getDetails()
    {
        Assert::isInstanceOf($this->server, Server::class);

        $this->repository->setServer($this->server);
        $this->allocationService->setServer($this->server);
        $this->cloudinitService->setServer($this->server);

        $config = Arr::keyBy($this->repository->getAllocations(), 'key');
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
                'cpu' => Arr::get($resources, 'maxcpu'),
                'memory' => Arr::get($config, 'memory.value', 0) * 1048576,
                'disk' => Arr::get($resources, 'maxdisk'),
                'addresses' => [
                    'ipv4' => $this->server->addresses()->where('type', AddressType::IPV4->value)->first(['address' ,'cidr', 'gateway', 'mac_address'])?->toArray(),
                    'ipv6' => $this->server->addresses()->where('type', AddressType::IPV6->value)->first(['address' ,'cidr', 'gateway'])?->toArray(),
                ]
            ],
            'configuration' => [
                'boot_order' => $this->allocationService->getBootOrder(),
                'disks' => $this->allocationService->getDisks(),
                'template' => Arr::get($resources, 'template'),
                'addresses' => $this->cloudinitService->getIpConfig(),
            ],
            'node' => $this->server->node->cluster,
        ];

        return $details;
    }
}