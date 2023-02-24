<?php

namespace Convoy\Services\Servers;

use Convoy\Data\Node\Access\CreateUserData;
use Convoy\Enums\Node\Access\RealmType;
use Convoy\Models\Server;
use Convoy\Models\User;
use Convoy\Repositories\Proxmox\Node\ProxmoxAccessRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxServerRepository;
use Convoy\Services\ProxmoxService;
use Exception;
use Illuminate\Support\Arr;

class VncService extends ProxmoxService
{
    public function __construct(private ProxmoxServerRepository $serverRepository, private ProxmoxAccessRepository $accessRepository)
    {
    }

    public function generateCredentials(Server $server)
    {
        $this->accessRepository->setServer($server);
        $this->serverRepository->setServer($server);

        $user = $this->accessRepository->createUser(CreateUserData::from([
            'id' => null,
            'password' => null,
            'realm_type' => 'pve',
            'enabled' => true,
            'expires_at' => now()->addDay(),
        ]));

        try {
            $this->accessRepository->createRole('convoy-vnc', 'VM.Audit,VM.Console');
        } catch (Exception $e) {
        }

        $this->serverRepository->addUser(
            RealmType::PVE,
            $user->id,
            'convoy-vnc'
        );

        $token = $this->accessRepository->getTicket(RealmType::PVE, $user->id, $user->password);

        return Arr::get($token, 'ticket');
    }
}
