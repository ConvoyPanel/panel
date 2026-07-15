<?php

namespace App\Services\Proxmox\Server;

use App\Services\Proxmox\ProxmoxClient;

class ProxmoxCloudinitClient extends ProxmoxClient
{
    public function getConfig()
    {
        $response = $this->getHttpClientWithParams()
            ->get('/api2/json/nodes/{node}/qemu/{server}/config')
            ->json();

        return $this->getData($response);
    }

    public function update(array $params = [])
    {
        $response = $this->getHttpClientWithParams()
            ->post('/api2/json/nodes/{node}/qemu/{server}/config', $params)
            ->json();

        return $this->getData($response);
    }
}
