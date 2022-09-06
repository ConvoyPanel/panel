<?php

namespace App\Models\Objects\Server;

use App\Models\Objects\Server\Configuration\ServerConfigurationObject;
use App\Models\Objects\Server\Limits\ServerLimitsObject;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class ServerDeploymentObject extends Data
{
    public function __construct(
        public string|Optional $type,
        public int|Optional $user_id,
        public int|Optional $node_id,
        public int|Optional $template_id,
        public string|Optional $name,
        public int|Optional $vmid,
        public ServerLimitsObject $limits,
        public ServerConfigurationObject $configuration,
    ) {
    }
}