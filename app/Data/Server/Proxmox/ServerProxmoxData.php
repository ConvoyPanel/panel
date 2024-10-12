<?php

namespace App\Data\Server\Proxmox;

use Spatie\LaravelData\Data;
use App\Data\Server\Proxmox\Config\ServerConfigData;

class ServerProxmoxData extends Data
{
    public function __construct(
        public int              $id,
        public string           $uuid_short,
        public string           $uuid,
        public int              $node_id,
        public string           $state,
        public bool             $locked,
        public ServerConfigData $config,
    ) {
    }
}
