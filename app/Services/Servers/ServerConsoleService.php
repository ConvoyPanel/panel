<?php

namespace App\Services\Servers;

use App\Data\Node\Access\CreateUserData;
use App\Data\Node\Access\UserCredentialsData;
use App\Data\Server\Proxmox\Console\NoVncCredentialsData;
use App\Data\Server\Proxmox\Console\XTermCredentialsData;
use App\Enums\Node\Access\RealmType;
use App\Models\Server;
use App\Services\Proxmox\Node\ProxmoxAccessClient;
use App\Services\Proxmox\Server\ProxmoxConsoleClient;
use App\Services\Proxmox\Server\ProxmoxServerClient;
use Exception;

class ServerConsoleService
{
    public function __construct(private ProxmoxServerClient $serverClient, private ProxmoxAccessClient $accessClient, private ProxmoxConsoleClient $consoleClient) {}

    public function createConsoleUserCredentials(Server $server): UserCredentialsData
    {
        $this->accessClient->setServer($server);
        $this->serverClient->setServer($server);

        $user = $this->accessClient->createUser(CreateUserData::from([
            'realmType' => 'pve',
            'enabled' => true,
            'expiresAt' => now()->addDay(),
        ]));

        try {
            $this->accessClient->createRole('convoy-console', 'VM.Audit,VM.Console');
        } catch (Exception) {
        }

        $this->serverClient->addUser(
            RealmType::PVE,
            $user->username,
            'convoy-console'
        );

        return $this->accessClient->createUserCredentials(RealmType::PVE, $user->username, $user->password);
    }

    public function createNoVncCredentials(Server $server): NoVncCredentialsData
    {
        $credentials = $this->createConsoleUserCredentials($server);

        return $this->consoleClient->setServer($server)->createNoVncCredentials($credentials);
    }

    public function createXTermjsCredentials(Server $server): XTermCredentialsData
    {
        $credentials = $this->createConsoleUserCredentials($server);

        return $this->consoleClient->setServer($server)->createXTermjsCredentials($credentials);
    }
}
