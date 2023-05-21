<?php

namespace Convoy\Repositories\Proxmox\Server;

use Convoy\Data\Node\Access\UserCredentialsData;
use Convoy\Data\Server\Proxmox\Console\NoVncCredentialsData;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\ProxmoxRepository;
use GuzzleHttp\Cookie\CookieJar;
use Webmozart\Assert\Assert;

class ProxmoxConsoleRepository extends ProxmoxRepository
{
    public function createNoVncCredentials(UserCredentialsData $credentials): NoVncCredentialsData
    {
        Assert::isInstanceOf($this->server, Server::class);

        $response = $this->getHttpClient(headers: [
            'CSRFPreventionToken' => $credentials->csrf_token
        ], options: [
            'cookies' => CookieJar::fromArray([
                'PVEAuthCookie' => $credentials->ticket,
            ], $this->node->fqdn)
        ], shouldAuthorize: false)
            ->withUrlParameters([
                'node' => $this->node->cluster,
                'server' => $this->server->vmid,
            ])
            ->post('/api2/json/nodes/{node}/qemu/{server}/vncproxy', [
                'websocket' => true
            ])
            ->json();



        $response = $this->getData($response);

        return NoVncCredentialsData::from([
            'port' => $response['port'],
            'ticket' => $response['ticket'],
            'pve_auth_cookie' => $credentials->ticket,
        ]);
    }
}