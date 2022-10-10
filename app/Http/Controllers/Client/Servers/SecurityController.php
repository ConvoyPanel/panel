<?php

namespace Convoy\Http\Controllers\Client\Servers;

use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxCloudinitRepository;
use Convoy\Services\Servers\VncService;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class SecurityController extends ApplicationApiController
{
    public function __construct(private ProxmoxCloudinitRepository $repository, private VncService $vncService)
    {
    }

    public function index(Server $server)
    {
        return inertia('servers/security/Index', [
            'server' => $server,
            'config' => $this->repository->setServer($server)->getConfig(),
        ]);
    }

    public function showVnc(Server $server)
    {
        return Inertia::render('servers/security/novnc/Index', [
            'server' => $server,
        ]);
    }

    public function getVncCredentials(Server $server)
    {
        $data = $this->vncService->setServer($server)->getTemporaryVncCredentials();

        if (! $data) {
            throw new ServiceUnavailableHttpException();
        }

        return array_merge([
            'node_id' => $server->node->cluster,
            'vmid' => $server->vmid,
            'token' => $data,
            'endpoint' => 'https://'.$server->node->hostname.':'.$server->node->port.'/novnc/novnc.html',
        ]);
    }
}
