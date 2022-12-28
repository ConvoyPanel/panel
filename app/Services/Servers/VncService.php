<?php

namespace Convoy\Services\Servers;

use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Node\ProxmoxAccessRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxServerRepository;
use Convoy\Services\ProxmoxService;
use Exception;
use Illuminate\Support\Arr;

class VncService extends ProxmoxService
{
    public function __construct(protected ProxmoxServerRepository $serverRepository, protected ProxmoxAccessRepository $accessRepository)
    {
    }

    public function generateCredentials(Server $server)
    {
        $this->accessRepository->setServer($server);
        $this->serverRepository->setServer($server);

        $user = $this->accessRepository->createUser();

        try {
            $this->accessRepository->createRole('convoy-vnc', 'VM.Audit,VM.Console');
        } catch (Exception $e) {
        }

        $this->serverRepository->addUser(
            $user['userid'],
            'convoy-vnc'
        );

        $token = $this->accessRepository->getTicket(Arr::first(explode('@', $user['userid'])), $user['password']);

        return Arr::get($token, 'ticket');
    }
}
