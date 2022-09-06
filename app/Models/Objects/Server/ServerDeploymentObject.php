<?php

namespace App\Models\Objects\Server;

use App\Models\Objects\Server\Configuration\ServerConfigurationObject;
use App\Models\Objects\Server\Limits\ServerLimitsObject;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class ServerDeploymentObject extends Data
{
    public function __construct(
        public string|null $type,
        public int|null $user_id,
        public int|null $node_id,
        public int|null $template_id,
        public string|null $name,
        public int|null $vmid,
        public ServerLimitsObject $limits,
        public ServerConfigurationObject $configuration,
    ) {
    }
}