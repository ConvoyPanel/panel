<?php

namespace App\Repositories\Proxmox\Server;

use App\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use App\Models\Node;
use App\Models\Server;
use App\Repositories\Proxmox\ProxmoxRepository;
use GuzzleHttp\Exception\GuzzleException;
use Webmozart\Assert\Assert;

class ProxmoxServerRepository extends ProxmoxRepository
{
    /**
     * @throws ProxmoxConnectionException
     */
    public function getStatus()
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->get(sprintf('/api2/json/nodes/%s/qemu/%s/status/current', $this->node->cluster, $this->server->vmid));
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException();
        }

        $data = json_decode($response->getBody(), true);
        return $data['data'] ?? $data;
    }

    public function getResources()
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->get('/api2/json/cluster/resources');
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        $json = json_decode($response->getBody(), true);
        $data = $json['data'] ?? $json;

        return collect($data)->where('vmid', $this->server->vmid)->firstOrFail();
    }

    public function create(int $template, int $targetVmid, array $params = [])
    {
        Assert::isInstanceOf($this->node, Node::class);

        try {
            $response = $this->getHttpClient()->post(sprintf('/api2/json/nodes/%s/qemu/%s/clone', $this->node->cluster, $template), [
                'json' => array_merge([
                    'target' => $this->node->cluster,
                    'newid' => $targetVmid,
                    'full' => true,
                ], $params)
            ]);
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        $json = json_decode($response->getBody(), true);
        return $json['data'] ?? $json;
    }
}