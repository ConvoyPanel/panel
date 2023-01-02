<?php

namespace Convoy\Repositories\Proxmox\Server;

use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\ProxmoxRepository;
use GuzzleHttp\Exception\GuzzleException;
use Webmozart\Assert\Assert;

class ProxmoxFirewallRepository extends ProxmoxRepository
{
    public function updateOptions(array $payload)
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->put(
                sprintf('/api2/json/nodes/%s/qemu/%s/firewall/options', $this->node->cluster, $this->server->vmid),
                [
                    'json' => $payload,
                ]
            );
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        return $this->getData($response);
    }
}
