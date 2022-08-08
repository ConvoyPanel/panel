<?php

namespace App\Services\Servers;

use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxAllocationRepository;
use App\Services\ProxmoxService;
use Webmozart\Assert\Assert;

class NetworkService extends ProxmoxService
{
    private ProxmoxAllocationRepository $allocationRepository;

    public function __construct()
    {
        $this->allocationRepository = new ProxmoxAllocationRepository;
    }

    public function deleteIpset(string $name)
    {
        $this->allocationRepository->setServer($this->server);

        $addresses = array_column($this->allocationRepository->getLockedIps($name), 'cidr');

        foreach ($addresses as $address)
        {
            $this->allocationRepository->unlockIp($name, $address);
        }

        return $this->allocationRepository->setServer($this->server)->deleteIpset($name);
    }

    public function clearIpsets()
    {
        $this->allocationRepository->setServer($this->server);

        $ipSets = array_column($this->allocationRepository->getIpsets(), 'name');

        foreach ($ipSets as $ipSet)
        {
            $this->deleteIpset($ipSet);
        }
    }

    public function lockIps(array $addresses, string $ipsetName = 'default')
    {
        $this->allocationRepository->setServer($this->server);

        $this->allocationRepository->createIpset($ipsetName);

        foreach ($addresses as $address)
        {
            $this->allocationRepository->lockIp($ipsetName, $address);
        }
    }
}