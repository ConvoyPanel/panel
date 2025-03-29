<?php

namespace App\Repositories\Proxmox\Node;

use App\Data\Node\Status\NodeStatusData;
use App\Repositories\Proxmox\ProxmoxRepository;

class ProxmoxStatusRepository extends ProxmoxRepository
{
    public function getStatus(): NodeStatusData
    {
        $response = $this->getHttpClientWithParams()
            ->get('/api2/json/nodes/{node}/status')
            ->json();

        return NodeStatusData::fromRaw($this->getData($response));
    }
}