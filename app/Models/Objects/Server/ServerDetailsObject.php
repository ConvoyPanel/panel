<?php

namespace App\Models\Objects\Server;

use App\Models\Objects\Server\Configuration\ServerConfigurationObject;
use App\Models\Objects\Server\Limits\ServerLimitsObject;
use App\Models\Objects\Server\Usage\ServerUsageObject;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class ServerDetailsObject extends Data
{
    public function __construct(
        public string|Optional $type,
        public int|Optional $user_id,
        public int $node_id,
        public int|Optional $template_id,
        public string|Optional $name,
        public int $vmid,
        public string|Optional $status,
        public bool|Optional $locked,
        public ServerUsageObject|Optional $usage,
        public ServerLimitsObject $limits,
        public ServerConfigurationObject $configuration,
    ) {
    }
}