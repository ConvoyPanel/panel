<?php

namespace Convoy\Repositories\Proxmox\Server;

use Convoy\Data\Node\Access\UserCredentialsData;
use Convoy\Data\Server\Proxmox\Console\NoVncCredentialsData;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\ProxmoxRepository;
use GuzzleHttp\RequestOptions;
use Webmozart\Assert\Assert;

class ProxmoxConsoleRepository extends ProxmoxRepository
{
    public function createNoVncCredentials(UserCredentialsData $credentials): NoVncCredentialsData
    {
        Assert::isInstanceOf($this->server, Server::class);

        $response = $this->getHttpClient(headers: [
            'Cookie' => "PVEAuthCookie={$credentials->ticket}",
            'CSRFPreventionToken' => $credentials->csrf_token,
        ], shouldAuthorize: false)
            ->withUrlParameters([
                'node' => $this->node->cluster,
                'server' => $this->server->vmid,
            ])
            ->post('/api2/json/nodes/{node}/qemu/{server}/vncproxy')
            ->json();

        $response = $this->getData($response);

        return NoVncCredentialsData::from([
            'vmid' => $response['vmid'],
            'port' => $response['port'],
            'ticket' => $response['ticket']
        ]);
    }
}