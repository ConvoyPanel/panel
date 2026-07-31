<?php

namespace App\Services\Proxmox\Server;

use App\Data\Server\Proxmox\ServerStateData;
use App\Enums\Node\Access\RealmType;
use App\Exceptions\Proxmox\RequestException;
use App\Models\Template;
use App\Services\Nodes\GuestStateCache;
use App\Services\Proxmox\ProxmoxClient;
use Illuminate\Http\Client\ConnectionException;

class ProxmoxServerClient extends ProxmoxClient
{
    public function __construct(private GuestStateCache $guestStates) {}

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getState(): ServerStateData
    {
        $response = $this->getHttpClientWithParams()
            ->get('/api2/json/nodes/{node}/qemu/{server}/status/current')
            ->json();

        $state = ServerStateData::fromRaw($this->getData($response));

        // Write-through: this is the only place in the app that reads one
        // guest's status live, so recording it here means no caller can forget
        // to. It is what keeps a server list -- which reads the cache and never
        // PVE -- from showing a stale badge in the minute after a power action,
        // and it costs one cache write on a request that just paid for a round
        // trip to Proxmox.
        $this->guestStates->observe($this->getServer(), $state->state);

        return $state;
    }

    /**
     * @return string Job UPID
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function create(Template $template): string
    {
        $server = $this->getServer();

        $response = $this->getHttpClientWithParams([
            'template' => $template->vmid,
        ])
            ->post('/api2/json/nodes/{node}/qemu/{template}/clone', [
                'storage' => $server->storage->name,
                'target' => $this->node->name,
                'newid' => $server->vmid,
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
//            ->withOptions([
//                'query' => [
//                    'destroy-unreferenced-disks' => true,
//                    'purge' => true,
//                ],
//            ])
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
