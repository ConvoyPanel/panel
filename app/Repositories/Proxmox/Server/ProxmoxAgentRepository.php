<?php

namespace Convoy\Repositories\Proxmox\Server;

use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\ProxmoxRepository;
use GuzzleHttp\Exception\GuzzleException;
use Webmozart\Assert\Assert;

class ProxmoxAgentRepository extends ProxmoxRepository
{

    public $infoActions = [
        'get-fsinfo',
        'get-host-name',
        'get-memory-block-info',
        'get-memory-blocks',
        'get-osinfo',
        'get-time',
        'get-timezone',
        'get-users',
        'get-vcpus',
        'info',
        'network-get-interfaces',
    ];

    public $suspendActions = [
        'suspend-disk',
        'suspend-hybrid',
        'suspend-ram',
    ];

    public function execAgent(array $params = [])
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->post(sprintf('/api2/json/nodes/%s/qemu/%s/agent/exec', $this->node->cluster, $this->server->vmid), [
                'json' => $params,
            ]);
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        return $this->getData($response);
    }

    public function shutdownAgent()
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->post(sprintf('/api2/json/nodes/%s/qemu/%s/agent/shutdown', $this->node->cluster, $this->server->vmid));
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        return $this->getData($response);
    }

    public function pingAgent()
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->post(sprintf('/api2/json/nodes/%s/qemu/%s/agent/ping', $this->node->cluster, $this->server->vmid));
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        return $this->getData($response);
    }

    public function getAgentInfo(string $infoActions)
    {
        Assert::isInstanceOf($this->server, Server::class);
        Assert::inArray($infoActions, $this->actions, 'Invalid action');

        try {
            $response = $this->getHttpClient()->get(sprintf('/api2/json/nodes/%s/qemu/%s/agent/%s', $this->node->cluster, $this->server->vmid, $infoActions), [
                'json' => [
                    'timeout' => 30,
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        return $this->getData($response);
    }

    public function suspendAgent(string $suspendActions)
    {
        Assert::isInstanceOf($this->server, Server::class);
        Assert::inArray($suspendActions, $this->actions, 'Invalid action');

        try {
            $response = $this->getHttpClient()->get(sprintf('/api2/json/nodes/%s/qemu/%s/agent/%s', $this->node->cluster, $this->server->vmid, $suspendActions), [
                'json' => [
                    'timeout' => 60,
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        return $this->getData($response);
    }

}