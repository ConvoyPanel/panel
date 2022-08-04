<?php

namespace App\Repositories\Proxmox\Server;

use App\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
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

        $data = json_decode($response->getBody()->__toString());
        return $data;
    }
}