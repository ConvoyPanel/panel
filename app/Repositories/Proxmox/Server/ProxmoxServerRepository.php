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

        return $this->getData($response);
    }

    public function create(int $template, array $params = [])
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->post(sprintf('/api2/json/nodes/%s/qemu/%s/clone', $this->node->cluster, $template), [
                'json' => array_merge([
                    'target' => $this->node->cluster,
                    'newid' => $this->server->vmid,
                    'full' => true,
                ], $params)
            ]);
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        return $this->getData($response);
    }

    public function delete(bool $destroyUnreferencedDisks = true, bool $purgeJobConfigurations = true, bool $skipLock = true)
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->delete(sprintf('/api2/json/nodes/%s/qemu/%s', $this->node->cluster, $this->server->vmid), [
                'destroy-unreferenced-disks' => $destroyUnreferencedDisks, 'purge' => $purgeJobConfigurations, 'skiplock' => $skipLock
            ]);
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException();
        }

        return $this->getData($response);
    }

    public function addUser(string $userid, string $roleid)
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->put('/api2/json/access/acl', [
                'path' => '/vms/' . $this->server->vmid,
                'users' => $userid,
                'roles' => $roleid,
            ]);
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException();
        }

        return $this->getData($response);
    }
}