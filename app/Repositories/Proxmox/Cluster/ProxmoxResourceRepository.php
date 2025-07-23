<?php

namespace App\Repositories\Proxmox\Cluster;

use App\Repositories\Proxmox\ProxmoxRepository;
use Illuminate\Http\Client\ConnectionException;
use App\Exceptions\Repository\Proxmox\RequestException;

class ProxmoxResourceRepository extends ProxmoxRepository
{
    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getResources(): array
    {
        $response = $this->getHttpClient()
            ->get('/api2/json/cluster/resources')
            ->json();

        return $this->getData($response);
    }
}