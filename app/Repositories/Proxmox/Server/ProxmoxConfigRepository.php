<?php

namespace App\Repositories\Proxmox\Server;

use App\Repositories\Proxmox\ProxmoxRepository;
use Illuminate\Http\Client\ConnectionException;

class ProxmoxConfigRepository extends ProxmoxRepository
{
    /**
     * @throws ConnectionException
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
     * @throws ConnectionException
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
     * @throws ConnectionException
     */
    public function update(array $payload = [])
    {
        $response = $this->getHttpClientWithParams()
            ->post('/api2/json/nodes/{node}/qemu/{server}/config', $payload)
            ->json();

        return $this->getData($response);
    }
}
