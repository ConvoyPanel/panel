<?php

namespace Convoy\Repositories\Proxmox\Server;

use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\ProxmoxRepository;
use GuzzleHttp\Exception\GuzzleException;
use Webmozart\Assert\Assert;

class ProxmoxPowerRepository extends ProxmoxRepository
{
    public $actions = [
        'reboot',
        'reset',
        'resume',
        'shutdown',
        'start',
        'stop',
        'suspend',
    ];

    public function send(string $action)
    {
        Assert::isInstanceOf($this->server, Server::class);
        Assert::inArray($action, $this->actions, 'Invalid action');

        try {
            $response = $this->getHttpClient()->post(sprintf('/api2/json/nodes/%s/qemu/%s/status/%s', $this->node->cluster, $this->server->vmid, $action), [
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
