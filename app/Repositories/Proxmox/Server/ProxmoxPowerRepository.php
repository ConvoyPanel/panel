<?php

namespace App\Repositories\Proxmox\Server;

use App\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use App\Models\Server;
use App\Repositories\Proxmox\ProxmoxRepository;
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
        'suspend'
    ];

    public function send(string $action)
    {
        Assert::isInstanceOf($this->server, Server::class);
        Assert::inArray($action, $this->actions, 'Invalid action');

        try {
            $response = $this->getHttpClient()->post(sprintf('/api2/json/nodes/%s/qemu/%s/status/%s', $this->node->cluster, $this->server->vmid, $action), [
                'json' => [
                    'timeout' => 30,
                ]
            ]);
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        return $this->getData($response);
    }
}