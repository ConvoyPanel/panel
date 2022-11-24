<?php

namespace Convoy\Repositories\Proxmox\Server;

use Convoy\Enums\Server\Power;
use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\ProxmoxRepository;
use GuzzleHttp\Exception\GuzzleException;
use Webmozart\Assert\Assert;

class ProxmoxPowerRepository extends ProxmoxRepository
{
    public function send(Power $action)
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->post(sprintf('/api2/json/nodes/%s/qemu/%s/status/%s', $this->node->cluster, $this->server->vmid, $action->value), [
                'json' => [
                    ...($action !== 'suspend' ? ['timeout' => 30] : ['skiplock' => 0])
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        return $this->getData($response);
    }
}
