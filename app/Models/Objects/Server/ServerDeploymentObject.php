<?php

namespace Convoy\Models\Objects\Server;

use Convoy\Models\Objects\Server\Configuration\ServerConfigObject;
use Convoy\Models\Objects\Server\Limits\ServerLimitsObject;
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
        public ServerConfigObject $config,
    ) {
    }
}