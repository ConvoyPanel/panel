<?php

namespace App\Services\Servers;

use App\Models\Server;
use App\Services\Nodes\Information\ResourceService as InformationResourceService;
use App\Services\ProxmoxService;

/**
 * Class SnapshotService
 * @package App\Services\Servers
 */
class ResourceService extends ProxmoxService
{
    private InformationResourceService $resourceService;

    public function __construct()
    {
        $this->resourceService = new InformationResourceService;
    }

    public function getResources()
    {
        $allResources = $this->resourceService->setServer($this->server)->getResourceList();

        $resources = array_search('qemu/'.$this->server->vmid, array_column($allResources, 'id'));

        return $allResources[$resources];
    }

    public function setMemory(int $bytes)
    {
        return $this->instance()->config()->put(['memory' => $bytes]);
    }

    public function setCores(int $cores, int $sockets)
    {
        return $this->instance()->config()->put(['cores' => $cores, 'sockets' => $sockets]);
    }

    public function increaseDisk(int $bytes, string $disk)
    {
        return $this->instance()->resize()->put(['disk' => $disk, 'size' => '+' . $bytes . 'B']);
    }
}