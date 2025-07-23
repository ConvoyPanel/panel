<?php

namespace App\Repositories\Proxmox\Node;

use App\Data\Node\Status\NodeStatusData;
use App\Repositories\Proxmox\ProxmoxRepository;
use Illuminate\Http\Client\ConnectionException;
use App\Exceptions\Repository\Proxmox\RequestException;

class ProxmoxStatusRepository extends ProxmoxRepository
{
    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getStatus(): NodeStatusData
    {
        $response = $this->getHttpClientWithParams()
            ->get('/api2/json/nodes/{node}/status')
            ->json();

        return NodeStatusData::fromRaw($this->getData($response));
    }
}