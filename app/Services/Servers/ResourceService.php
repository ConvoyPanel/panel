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
    public function getResources()
    {
        $resourceService = new InformationResourceService;

        $allResources = $resourceService->setServer($this->server)->getResourceList();

        $resources = array_search('qemu/'.$this->server->vmid, array_column($allResources, 'id'));

        return $allResources[$resources];
    }
}