<?php

namespace App\Services\Servers;

use App\Repositories\Proxmox\Server\ProxmoxFirewallRepository;

class ServerNetworkBandwidthService {
    public function __construct(private ProxmoxFirewallRepository $repository)
    {
    }

    public function setRateLimit(Server $server, int $rate): void
    {
        $this->firewallRepository->setServer($server)->setRateLimit($mebibytes);
    }
}