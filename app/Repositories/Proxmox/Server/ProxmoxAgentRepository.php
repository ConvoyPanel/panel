<?php

namespace Convoy\Repositories\Proxmox\Server;

use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\ProxmoxRepository;
use GuzzleHttp\Exception\GuzzleException;
use Webmozart\Assert\Assert;

class ProxmoxAgentRepository extends ProxmoxRepository
{

    public $get_Info_Agent = [
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

    public function exec(array $params = [])
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

    public function shutdown()
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->post(sprintf('/api2/json/nodes/%s/qemu/%s/agent/shutdown', $this->node->cluster, $this->server->vmid));
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        return $this->getData($response);
    }

    public function ping()
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->post(sprintf('/api2/json/nodes/%s/qemu/%s/agent/ping', $this->node->cluster, $this->server->vmid));
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        return $this->getData($response);
    }

    public function getAgentInfo(string $get_Info_Agent)
    {
        Assert::isInstanceOf($this->server, Server::class);
        Assert::inArray($get_Info_Agent, $this->actions, 'Invalid action');

        try {
            $response = $this->getHttpClient()->get(sprintf('/api2/json/nodes/%s/qemu/%s/agent/%s', $this->node->cluster, $this->server->vmid, $get_Info_Agent), [
                'json' => [
                    'timeout' => 30,
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        return $this->getData($response);
    }

    public function getAgentActions(string $suspendActions)
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