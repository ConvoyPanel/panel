<?php

namespace App\Services\Servers;

use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxConfigRepository;

class ServerAuthService
{
    public function __construct(private ProxmoxConfigRepository $configRepository)
    {
    }

    public function setPassword(Server $server, string $password): void
    {
        $this->configRepository->setServer($server)->update(['cipassword' => $password]);
    }

    public function getSSHKeys(Server $server): string
    {
        $raw = collect($this->configRepository->setServer($server)->getConfig())->where('key', '=', 'sshkeys')->first()['value'] ?? '';

        return rawurldecode($raw);
    }

    public function setSSHKeys(Server $server, ?string $keys): void
    {
        if (! empty($keys)) {
            $this->configRepository->setServer($server)->update(['sshkeys' => rawurlencode($keys)]);
        } else {
            $this->configRepository->setServer($server)->update(['delete' => 'sshkeys']);
        }
    }
}
