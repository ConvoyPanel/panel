<?php

namespace Convoy\Services\Servers;

use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxConfigRepository;

class ServerAuthenticationService
{
    public function __construct(private ProxmoxConfigRepository $configRepository)
    {
    }

    public function updatePassword(Server $server, ?string $password)
    {
        if (!empty($password)) {
            $this->configRepository->setServer($server)->update(['cipassword' => rawurlencode($password)]);
        } else {
            $this->configRepository->setServer($server)->update(['delete' => 'cipassword']);
        }
    }
}