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
     * @throws ConnectionException
     */
    public function getConfig(): ServerConfigData
    {
        $response = $this->getHttpClientWithParams()
            ->get('/api2/json/nodes/{node}/qemu/{server}/config')
            ->json();
        
        return ServerConfigData::fromRaw($this->getData($response));
    }

    /**
     * Update the VM config. Pass the digest captured from getConfig() to make
     * PVE reject the write if the config changed since it was read (optimistic
     * concurrency); a mismatch surfaces as a RequestException.
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function update(array $payload = [], ?string $digest = null)
    {
        if ($digest !== null) {
            $payload['digest'] = $digest;
        }

        $response = $this->getHttpClientWithParams()
            ->post('/api2/json/nodes/{node}/qemu/{server}/config', $payload)
            ->json();

        return $this->getData($response);
    }
}
