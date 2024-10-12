<?php

namespace App\Services\Servers;

use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxConfigRepository;

class ServerAuthService
{
    public function __construct(private ProxmoxConfigRepository $configRepository)
    {
    }

    public function updatePassword(Server $server, string $password)
    {
        // if (!empty($password)) {
        $this->configRepository->setServer($server)->update(['cipassword' => $password]);
        // } else {
        //     $this->configRepository->setServer($server)->update(['delete' => 'cipassword']);
        // }
    }

    public function getSSHKeys(Server $server)
    {
        $raw = collect($this->configRepository->setServer($server)->getConfig())->where('key', '=', 'sshkeys')->first()['value'] ?? '';

        return rawurldecode($raw);
    }

    public function updateSSHKeys(Server $server, ?string $keys)
    {
        if (! empty($keys)) {
            $this->configRepository->setServer($server)->update(['sshkeys' => rawurlencode($keys)]);
        } else {
            $this->configRepository->setServer($server)->update(['delete' => 'sshkeys']);
        }
    }
}
