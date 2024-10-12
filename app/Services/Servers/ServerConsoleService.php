<?php

namespace App\Services\Servers;

use Exception;
use App\Models\Server;
use App\Enums\Node\Access\RealmType;
use App\Data\Node\Access\CreateUserData;
use App\Data\Node\Access\UserCredentialsData;
use App\Data\Server\Proxmox\Console\NoVncCredentialsData;
use App\Data\Server\Proxmox\Console\XTermCredentialsData;
use App\Repositories\Proxmox\Node\ProxmoxAccessRepository;
use App\Repositories\Proxmox\Server\ProxmoxServerRepository;
use App\Repositories\Proxmox\Server\ProxmoxConsoleRepository;

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
