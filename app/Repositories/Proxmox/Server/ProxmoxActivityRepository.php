<?php

namespace App\Repositories\Proxmox\Server;

use App\Models\Node;
use App\Models\Server;
use Webmozart\Assert\Assert;
use App\Repositories\Proxmox\ProxmoxRepository;

class ProxmoxActivityRepository extends ProxmoxRepository
{
    public function getLogs(int $startAt = 0, int $limitRows = 500)
    {
        Assert::isInstanceOf($this->server, Server::class);

        $response = $this->getHttpClient()
            ->withUrlParameters([
                'node' => $this->node->cluster,
            ])
            ->get('/api2/json/nodes/{node}/tasks', ['vmid' => $this->server->vmid, 'start' => $startAt, 'limit' => $limitRows])
            ->json();

        return $this->getData($response);
    }

    public function getStatus(string $upid)
    {
        Assert::isInstanceOf($this->node, Node::class);

        $response = $this->getHttpClient()
            ->withUrlParameters([
                'node' => $this->node->cluster,
                'task' => $upid,
            ])
            ->get('/api2/json/nodes/{node}/tasks/{task}/status')
            ->json();

        return $this->getData($response);
    }

    public function getLog(string $upid, int $startAt = 0, int $limitLinesTo = 100)
    {
        Assert::isInstanceOf($this->node, Node::class);

        $response = $this->getHttpClient()
            ->withUrlParameters([
                'node' => $this->node->cluster,
                'task' => $upid,
            ])
            ->get('/api2/json/nodes/{node}/tasks/{task}/log', [
                'start' => $startAt,
                'limit' => $limitLinesTo,
            ])
            ->json();

        return $this->getData($response);
    }

    public function delete(string $upid)
    {
        Assert::isInstanceOf($this->node, Node::class);

        $response = $this->getHttpClient()
            ->withUrlParameters([
                'node' => $this->node->cluster,
                'task' => $upid,
            ])
            ->delete('/api2/json/nodes/{node}/tasks/{task}')
            ->json();

        return $this->getData($response);
    }
}
