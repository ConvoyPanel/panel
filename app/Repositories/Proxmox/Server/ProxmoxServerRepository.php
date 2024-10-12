<?php

namespace App\Repositories\Proxmox\Server;

use App\Data\Server\Proxmox\ServerStateData;
use App\Enums\Node\Access\RealmType;
use App\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use App\Models\Server;
use App\Models\Template;
use App\Repositories\Proxmox\ProxmoxRepository;
use Webmozart\Assert\Assert;

class ProxmoxServerRepository extends ProxmoxRepository
{
    /**
     * @throws ProxmoxConnectionException
     */
    public function getState()
    {
        Assert::isInstanceOf($this->server, Server::class);

        $response = $this->getHttpClient()
                         ->withUrlParameters([
                             'node' => $this->node->cluster,
                             'server' => $this->server->vmid,
                         ])
                         ->get('/api2/json/nodes/{node}/qemu/{server}/status/current')
                         ->json();

        return ServerStateData::fromRaw($this->getData($response));
    }

    public function create(Template $template)
    {
        Assert::isInstanceOf($this->server, Server::class);

        $response = $this->getHttpClient()
                         ->withUrlParameters([
                             'node' => $this->node->cluster,
                             'template' => $template->vmid,
                         ])
                         ->post('/api2/json/nodes/{node}/qemu/{template}/clone', [
                             'storage' => $this->node->vm_storage,
                             'target' => $this->node->cluster,
                             'newid' => $this->server->vmid,
                             'full' => true,
                         ])
                         ->json();

        return $this->getData($response);
    }

    public function delete()
    {
        Assert::isInstanceOf($this->server, Server::class);

        $response = $this->getHttpClient(options: [
            'query' => [
                'destroy-unreferenced-disks' => true,
                'purge' => true,
            ],
        ])
                         ->withUrlParameters([
                             'node' => $this->node->cluster,
                             'server' => $this->server->vmid,
                         ])
                         ->delete('/api2/json/nodes/{node}/qemu/{server}')
                         ->json();

        return $this->getData($response);
    }

    public function addUser(RealmType $realmType, string $userId, string $roleId)
    {
        Assert::isInstanceOf($this->server, Server::class);

        $response = $this->getHttpClient()
                         ->put('/api2/json/access/acl', [
                             'path' => '/vms/' . $this->server->vmid,
                             'users' => $userId . '@' . $realmType->value,
                             'roles' => $roleId,
                         ])
                         ->json();

        return $this->getData($response);
    }
}
