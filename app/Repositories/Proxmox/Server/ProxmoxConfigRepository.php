<?php

namespace Convoy\Repositories\Proxmox\Server;

use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\ProxmoxRepository;
use GuzzleHttp\Exception\GuzzleException;
use Webmozart\Assert\Assert;

class ProxmoxConfigRepository extends ProxmoxRepository
{
    public function getConfig()
    {
        Assert::isInstanceOf($this->server, Server::class);

        $response = $this->getHttpClient()
            ->withUrlParameters([
                'node' => $this->node->cluster,
                'server' => $this->server->vmid
            ])
            ->get('/api2/json/nodes/{node}/qemu/{server}/config')
            ->json();


        $unparsed = $this->getData($response);
        $parsed = [];

        foreach ($unparsed as $key => $value ) {
            $parsed[] = [
                'key' => $key,
                'value' => $value,
            ];
        }

        return $parsed;
    }

    public function update(array $payload = [], bool $put = false)
    {
        Assert::isInstanceOf($this->server, Server::class);

        $response = $this->getHttpClient()
            ->withUrlParameters([
                'node' => $this->node->cluster,
                'server' => $this->server->vmid
            ])
            ->post('/api2/json/nodes/{node}/qemu/{server}/config', $payload)
            ->json();

        return $this->getData($response);
    }
}
