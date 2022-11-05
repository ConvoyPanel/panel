<?php

namespace Convoy\Models\Objects\Server;

use Convoy\Models\Objects\Server\Configuration\ServerConfigObject;
use Convoy\Models\Objects\Server\Limits\ServerLimitsObject;
use Convoy\Models\Objects\Server\Usage\ServerUsageObject;
use Spatie\LaravelData\Data;

class ServerDetailsObject extends Data
{
    public function __construct(
        public int $node_id,
        public int $vmid,
        public string $status,
        public bool $locked,
        public ServerUsageObject $usage,
        public ServerLimitsObject $limits,
        public ServerConfigObject $config,
    ) {
    }
}