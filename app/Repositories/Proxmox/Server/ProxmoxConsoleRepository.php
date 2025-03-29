<?php

namespace App\Repositories\Proxmox\Server;

use App\Data\Node\Access\UserCredentialsData;
use App\Data\Server\Proxmox\Console\NoVncCredentialsData;
use App\Data\Server\Proxmox\Console\XTermCredentialsData;
use App\Repositories\Proxmox\ProxmoxRepository;
use GuzzleHttp\Cookie\CookieJar;

class ProxmoxConsoleRepository extends ProxmoxRepository
{
    public function createNoVncCredentials(UserCredentialsData $credentials): NoVncCredentialsData
    {
        $response = $this->getHttpClientWithParams(shouldAuthorize: false)
            ->withHeader('CSRFPreventionToken', $credentials->csrfToken)
            ->withOptions([
                'cookies' => CookieJar::fromArray([
                    'PVEAuthCookie' => $credentials->ticket,
                ], $this->node->fqdn),
            ])
            ->post('/api2/json/nodes/{node}/qemu/{server}/vncproxy', [
                'websocket' => true,
            ])
            ->json();

        $response = $this->getData($response);

        return NoVncCredentialsData::from([
            'port' => $response['port'],
            'ticket' => $response['ticket'],
            'pveAuthCookie' => $credentials->ticket,
        ]);
    }

    public function createXTermjsCredentials(UserCredentialsData $credentials): XTermCredentialsData
    {
        $response = $this->getHttpClientWithParams(shouldAuthorize: false)
            ->withHeader('CSRFPreventionToken', $credentials->csrfToken)
            ->withOptions([
                'cookies' => CookieJar::fromArray([
                    'PVEAuthCookie' => $credentials->ticket,
                ], $this->node->fqdn),
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
            'realmType' => $credentials->realmType,
            'pveAuthCookie' => $credentials->ticket,
        ]);
    }
}
