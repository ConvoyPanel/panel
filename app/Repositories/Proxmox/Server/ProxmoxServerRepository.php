<?php

namespace App\Repositories\Proxmox\Server;

use App\Data\Server\Proxmox\ServerStateData;
use App\Enums\Node\Access\RealmType;
use App\Models\Template;
use App\Repositories\Proxmox\ProxmoxRepository;
use Illuminate\Http\Client\ConnectionException;
use App\Exceptions\Repository\Proxmox\RequestException;

class ProxmoxServerRepository extends ProxmoxRepository
{
    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getState(): ServerStateData
    {
        $response = $this->getHttpClientWithParams()
            ->get('/api2/json/nodes/{node}/qemu/{server}/status/current')
            ->json();

        return ServerStateData::fromRaw($this->getData($response));
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     *
     * @return string Job UPID
     */
    public function create(Template $template): string
    {
        $response = $this->getHttpClientWithParams([
            'template' => $template->vmid,
        ])
            ->post('/api2/json/nodes/{node}/qemu/{template}/clone', [
                'storage' => $this->server->storage->name,
                'target' => $this->node->name,
                'newid' => $this->server->vmid,
                'full' => true,
            ])
            ->json();

        return $this->getData($response);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function delete()
    {
        $response = $this->getHttpClientWithParams()
            ->withOptions([
                'query' => [
                    'destroy-unreferenced-disks' => true,
                    'purge' => true,
                ],
            ])
            ->delete('/api2/json/nodes/{node}/qemu/{server}')
            ->json();

        return $this->getData($response);
    }

    public function addUser(RealmType $realmType, string $userId, string $roleId)
    {
        $response = $this->getHttpClient()
            ->put('/api2/json/access/acl', [
                'path' => '/vms/'.$this->server->vmid,
                'users' => $userId.'@'.$realmType->value,
                'roles' => $roleId,
            ])
            ->json();

        return $this->getData($response);
    }
}
