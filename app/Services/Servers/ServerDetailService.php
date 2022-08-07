<?php

namespace App\Services\Servers;

use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxAllocationRepository;
use App\Services\ProxmoxService;
use Illuminate\Support\Arr;
use Webmozart\Assert\Assert;

class ServerDetailService extends ProxmoxService
{
    private ProxmoxAllocationRepository $repository;
    private AllocationService $allocationService;

    public function __construct()
    {
        $this->repository = new ProxmoxAllocationRepository;
        $this->allocationService = new AllocationService;
    }

    public function getDetails()
    {
        Assert::isInstanceOf($this->server, Server::class);

        $this->repository->setServer($this->server);
        $this->allocationService->setServer($this->server);

        $config = Arr::keyBy($this->repository->getAllocations(), 'key');
        $resources = $this->repository->getResources();

        $details = [
            'vmid' => $this->server->vmid,
            'status' => Arr::get($resources, 'status'),
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
                'cpu' => Arr::get($config, 'cores'),
                'memory' => Arr::get($config, 'memory'),
                'disk' => Arr::get($resources, 'maxdisk'),
            ],
            'configuration' => [
                'boot_order' => $this->allocationService->getBootOrder(),
                'disks' => $this->allocationService->getDisks(),
            ],
            'node' => $this->server->node->cluster,
        ];

        return $details;
    }
}