<?php

namespace App\Repositories\Proxmox\Server;

use App\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use App\Models\Server;
use App\Repositories\Proxmox\ProxmoxRepository;
use GuzzleHttp\Exception\GuzzleException;
use Webmozart\Assert\Assert;

class ProxmoxPowerRepository extends ProxmoxRepository
{
    public function send(string $action)
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->post(sprintf('/api2/json/nodes/%s/qemu/%s/status/%s', $this->node->cluster, $this->server->vmid, $action));
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        return $response;
    }
}