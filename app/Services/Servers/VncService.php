<?php

namespace App\Services\Servers;

use App\Repositories\Proxmox\Node\ProxmoxAccessRepository;
use App\Repositories\Proxmox\Server\ProxmoxServerRepository;
use App\Services\ProxmoxService;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;

class VncService extends ProxmoxService
{
    private ProxmoxServerRepository $serverRepository;
    private ProxmoxAccessRepository $accessRepository;

    public function __construct()
    {
        $this->serverRepository = new ProxmoxServerRepository;
        $this->accessRepository = new ProxmoxAccessRepository;
    }

    public function getTemporaryVncCredentials()
    {
        $this->accessRepository->setServer($this->server);
        $this->serverRepository->setServer($this->server);

        $user = $this->accessRepository->createUser();

        try {
            $this->accessRepository->createRole('convoy-vnc', 'VM.Console');
        } catch (Exception $e) {}

        $this->serverRepository->addUser(
            $user['userid'],
            'convoy-vnc'
        );

        $token = $this->accessRepository->getTicket(Arr::first(explode('@', $user['userid'])), $user['password']);

        return Arr::get($token, 'ticket');
    }
}
