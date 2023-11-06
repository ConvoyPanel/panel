<?php

namespace Convoy\Repositories\Proxmox\Server;

use Convoy\Models\Server;
use Webmozart\Assert\Assert;
use Convoy\Repositories\Proxmox\ProxmoxRepository;
use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;

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
