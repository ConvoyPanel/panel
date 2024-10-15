<?php

namespace App\Repositories\Proxmox\Server;

use App\Data\Node\Access\UserCredentialsData;
use App\Data\Server\Proxmox\Console\NoVncCredentialsData;
use App\Data\Server\Proxmox\Console\XTermCredentialsData;
use App\Models\Server;
use App\Repositories\Proxmox\ProxmoxRepository;
use GuzzleHttp\Cookie\CookieJar;
use Webmozart\Assert\Assert;

class ProxmoxConsoleRepository extends ProxmoxRepository
{
    public function createNoVncCredentials(UserCredentialsData $credentials): NoVncCredentialsData
    {
        Assert::isInstanceOf($this->server, Server::class);

        $response = $this->getHttpClient(headers: [
            'CSRFPreventionToken' => $credentials->csrf_token,
        ], options: [
            'cookies' => CookieJar::fromArray([
                'PVEAuthCookie' => $credentials->ticket,
            ], $this->node->fqdn),
        ], shouldAuthorize: false)
            ->withUrlParameters([
                'node' => $this->node->cluster,
                'server' => $this->server->vmid,
            ])
            ->post('/api2/json/nodes/{node}/qemu/{server}/vncproxy', [
                'websocket' => true,
            ])
            ->json();

        $response = $this->getData($response);

        return NoVncCredentialsData::from([
            'port' => $response['port'],
            'ticket' => $response['ticket'],
            'pve_auth_cookie' => $credentials->ticket,
        ]);
    }

    public function createXTermjsCredentials(UserCredentialsData $credentials): XTermCredentialsData
    {
        Assert::isInstanceOf($this->server, Server::class);

        $response = $this->getHttpClient(headers: [
            'CSRFPreventionToken' => $credentials->csrf_token,
        ], options: [
            'cookies' => CookieJar::fromArray([
                'PVEAuthCookie' => $credentials->ticket,
            ], $this->node->fqdn),
        ], shouldAuthorize: false)
            ->withUrlParameters([
                'node' => $this->node->cluster,
                'server' => $this->server->vmid,
            ])
            ->post('/api2/json/nodes/{node}/qemu/{server}/termproxy', [
                'vmid' => $this->server->vmid, // this is to fix the "NOT A HASH REFERENCE" stupid error Proxmox has if there's no JSON body
                // bruh fix ur shit proxmox
            ])
            ->json();

        $response = $this->getData($response);

        return XTermCredentialsData::from([
            'port' => $response['port'],
            'ticket' => $response['ticket'],
            'username' => $credentials->username,
            'realm_type' => $credentials->realm_type,
            'pve_auth_cookie' => $credentials->ticket,
        ]);
    }
}
