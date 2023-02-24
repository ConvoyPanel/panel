<?php

namespace Convoy\Repositories\Proxmox\Server;

use Convoy\Enums\Node\Access\RealmType;
use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Convoy\Models\Server;
use Convoy\Models\Template;
use Convoy\Repositories\Proxmox\ProxmoxRepository;
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
            throw new ProxmoxConnectionException($e);
        }

        return $this->getData($response);
    }

    public function create(Template $template, array $params = [])
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->post(sprintf('/api2/json/nodes/%s/qemu/%s/clone', $this->node->cluster, $template->vmid), [
                'json' => array_merge([
                    'target' => $this->node->cluster,
                    'newid' => $this->server->vmid,
                    'full' => true,
                ], $params),
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
                'destroy-unreferenced-disks' => $destroyUnreferencedDisks, 'purge' => $purgeJobConfigurations, 'skiplock' => $skipLock,
            ]);
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        return $this->getData($response);
    }

    public function addUser(RealmType $realmType, string $userid, string $roleid)
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->put('/api2/json/access/acl', [
                'json' => [
                    'path' => '/vms/'.$this->server->vmid,
                    'users' => $userid.'@'.$realmType->value,
                    'roles' => $roleid,
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        return $this->getData($response);
    }
}
