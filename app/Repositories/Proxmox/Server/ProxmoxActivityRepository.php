<?php

namespace App\Repositories\Proxmox\Server;

use App\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use App\Models\Node;
use App\Models\Server;
use App\Repositories\Proxmox\ProxmoxRepository;
use GuzzleHttp\Exception\GuzzleException;
use Webmozart\Assert\Assert;

class ProxmoxActivityRepository extends ProxmoxRepository
{
    public function getLogs(int $startAt = 0, int $limitRows = 500)
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->get(sprintf('/api2/json/nodes/%s/tasks', $this->node->cluster), [
                'query' => ['vmid' => $this->server->vmid, 'start' => $startAt, 'limit' => $limitRows]
            ]);
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        return $this->getData($response);
    }

    public function getStatus(string $upid)
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->get(sprintf('/api2/json/nodes/%s/tasks/%s/status', $this->node->cluster, $upid));
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        return $this->getData($response);
    }

    public function getLog(string $upid, int $startAt = 0, int $limitLines = 100)
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->get(sprintf('/api2/json/nodes/%s/tasks/%s/log', $this->node->cluster, $upid), [
                'query' => [
                     'start' => $startAt,
                    'limit' => $limitLines,
                ]
            ]);
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        return $this->getData($response);
    }

    public function delete(string $upid)
    {
        Assert::isInstanceOf($this->node, Node::class);

        try {
            $response = $this->getHttpClient()->delete(sprintf('/api2/json/nodes/%s/tasks/%s', $this->node->cluster, $upid));
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        return $this->getData($response);
    }
}