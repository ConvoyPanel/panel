<?php

namespace Convoy\Data\Server\Proxmox;

use Convoy\Data\Server\Proxmox\Config\ServerConfigData;
use Spatie\LaravelData\Data;

class ServerProxmoxData extends Data
{
    public function __construct(
        public int $id,
        public string $uuid_short,
        public string $uuid,
        public int $node_id,
        public string $state,
        public bool $locked,
        public ServerConfigData $config,
    ) {
    }
}
