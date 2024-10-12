<?php

namespace Convoy\Services\Servers;

use Convoy\Data\Node\Access\CreateUserData;
use Convoy\Data\Node\Access\UserCredentialsData;
use Convoy\Data\Server\Proxmox\Console\NoVncCredentialsData;
use Convoy\Data\Server\Proxmox\Console\XTermCredentialsData;
use Convoy\Enums\Node\Access\RealmType;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Node\ProxmoxAccessRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxConsoleRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxServerRepository;
use Exception;

class ServerConsoleService
{
    public function __construct(private ProxmoxServerRepository $serverRepository, private ProxmoxAccessRepository $accessRepository, private ProxmoxConsoleRepository $consoleRepository)
    {
    }

    public function createConsoleUserCredentials(Server $server): UserCredentialsData
    {
        $this->accessRepository->setServer($server);
        $this->serverRepository->setServer($server);

        $user = $this->accessRepository->createUser(CreateUserData::from([
            'realm_type' => 'pve',
            'enabled' => true,
            'expires_at' => now()->addDay(),
        ]));

        try {
            $this->accessRepository->createRole('convoy-console', 'VM.Audit,VM.Console');
        } catch (Exception) {
        }

        $this->serverRepository->addUser(
            RealmType::PVE,
            $user->username,
            'convoy-console'
        );

        return $this->accessRepository->createUserCredentials(RealmType::PVE, $user->username, $user->password);
    }

    public function createNoVncCredentials(Server $server): NoVncCredentialsData
    {
        $credentials = $this->createConsoleUserCredentials($server);

        return $this->consoleRepository->setServer($server)->createNoVncCredentials($credentials);
    }

    public function createXTermjsCredentials(Server $server): XTermCredentialsData
    {
        $credentials = $this->createConsoleUserCredentials($server);

        return $this->consoleRepository->setServer($server)->createXTermjsCredentials($credentials);
    }
}
