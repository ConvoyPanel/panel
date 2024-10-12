<?php

namespace App\Repositories\Proxmox\Server;

use App\Models\Server;
use Webmozart\Assert\Assert;
use App\Repositories\Proxmox\ProxmoxRepository;
use App\Exceptions\Repository\Proxmox\ProxmoxConnectionException;

class ProxmoxCloudinitRepository extends ProxmoxRepository
{
    /**
     * @return mixed
     *
     * @throws ProxmoxConnectionException
     */
    public function getConfig()
    {
        Assert::isInstanceOf($this->server, Server::class);

        $response = $this->getHttpClient()
            ->withUrlParameters([
                'node' => $this->node->cluster,
                'server' => $this->server->vmid,
            ])
            ->get('/api2/json/nodes/{node}/qemu/{server}/config')
            ->json();

        return $this->getData($response);
    }

    public function update(array $params = [])
    {
        Assert::isInstanceOf($this->server, Server::class);

        $response = $this->getHttpClient()
            ->withUrlParameters([
                'node' => $this->node->cluster,
                'server' => $this->server->vmid,
            ])
            ->post('/api2/json/nodes/{node}/qemu/{server}/config', $params)
            ->json();

        return $this->getData($response);
    }
}
