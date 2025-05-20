<?php

namespace App\Repositories\Proxmox\Server;

use App\Exceptions\Repository\Proxmox\RequestException;
use App\Repositories\Proxmox\ProxmoxRepository;
use Illuminate\Http\Client\ConnectionException;

class ProxmoxConfigRepository extends ProxmoxRepository
{
    /**
     * @throws RequestException
     */
    public function getConfig(): array
    {
        $response = $this->getHttpClientWithParams()
            ->get('/api2/json/nodes/{node}/qemu/{server}/config')
            ->json();

        $unparsed = $this->getData($response);
        $parsed = [];

        foreach ($unparsed as $key => $value) {
            $parsed[] = [
                'key' => $key,
                'value' => $value,
            ];
        }

        return $parsed;
    }

    /**
     * @throws RequestException
     */
    public function getResources()
    {
        $server = $this->getServer();

        $response = $this->getHttpClient()
            ->get('/api2/json/cluster/resources')
            ->json();

        $data = $this->getData($response);

        return collect($data)->where('vmid', $server->vmid)->firstOrFail();
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
