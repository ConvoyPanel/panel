<?php

namespace App\Services\Proxmox\Node;

use App\Data\Node\Status\NodeStatusData;
use App\Exceptions\Proxmox\RequestException;
use App\Services\Proxmox\ProxmoxClient;
use Illuminate\Http\Client\ConnectionException;

class ProxmoxStatusClient extends ProxmoxClient
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
