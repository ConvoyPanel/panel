<?php

namespace Convoy\Repositories\Proxmox\Server;

use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\ProxmoxRepository;
use Webmozart\Assert\Assert;

class ProxmoxSnapshotRepository extends ProxmoxRepository
{
    public function getSnapshots()
    {
        Assert::isInstanceOf($this->server, Server::class);

        $response = $this->getHttpClient()
            ->withUrlParameters([
                'node' => $this->node->cluster,
                'server' => $this->server->vmid,
            ])
            ->get('/api2/json/nodes/{node}/qemu/{server}/snapshot')
            ->json();

        return $this->getData($response);
    }

    public function create(string $name)
    {
        Assert::isInstanceOf($this->server, Server::class);

        $response = $this->getHttpClient()
            ->withUrlParameters([
                'node' => $this->node->cluster,
                'server' => $this->server->vmid,
            ])
            ->post('/api2/json/nodes/{node}/qemu/{server}/snapshot', [
                'snapname' => $name,
            ])
            ->json();

        return $this->getData($response);
    }

    public function restore(string $name)
    {
        Assert::isInstanceOf($this->server, Server::class);

        $response = $this->getHttpClient()
            ->withUrlParameters([
                'node' => $this->node->cluster,
                'server' => $this->server->vmid,
                'snapshot' => $name,
            ])
            ->post('/api2/json/nodes/{node}/qemu/{server}/snapshot/{snapshot}/rollback')
            ->json();

        return $this->getData($response);
    }

    public function delete(string $name)
    {
        Assert::isInstanceOf($this->server, Server::class);

        $response = $this->getHttpClient()
            ->withUrlParameters([
                'node' => $this->node->cluster,
                'server' => $this->server->vmid,
                'snapshot' => $name,
            ])
            ->delete('/api2/json/nodes/{node}/qemu/{server}/snapshot/{snapshot}')
            ->json();

        return $this->getData($response);
    }
}
