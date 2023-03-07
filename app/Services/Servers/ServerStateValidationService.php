<?php

namespace Convoy\Services\Servers;

use Convoy\Enums\Server\PowerAction;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxServerRepository;

class ServerStateValidationService
{
    public function __construct(private ProxmoxServerRepository $repository)
    {

    }

    public function handle(Server $server, PowerAction $state)
    {

    }
}