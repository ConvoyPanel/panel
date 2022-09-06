<?php

namespace App\Models\Objects\Server;

use App\Models\Objects\Server\Configuration\ServerConfigObject;
use App\Models\Objects\Server\Limits\ServerLimitsObject;
use App\Models\Objects\Server\Usage\ServerUsageObject;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

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