<?php

namespace App\Repositories\Proxmox\Server;

use App\Data\Server\Proxmox\Config\ServerConfigData;
use App\Exceptions\Repository\Proxmox\RequestException;
use App\Repositories\Proxmox\ProxmoxRepository;
use Illuminate\Http\Client\ConnectionException;

class ProxmoxConfigRepository extends ProxmoxRepository
{
    /**
     * @throws RequestException
     */
    public function getConfig(): ServerConfigData
    {
        $response = $this->getHttpClientWithParams()
            ->get('/api2/json/nodes/{node}/qemu/{server}/config')
            ->json();



        return ServerConfigData::fromRaw($this->getData($response));
    }

    /**
     * @throws RequestException
     */
    public function update(array $payload = [])
    {
        $response = $this->getHttpClientWithParams()
            ->post('/api2/json/nodes/{node}/qemu/{server}/config', $payload)
            ->json();

        return $this->getData($response);
    }
}
