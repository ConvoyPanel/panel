<?php

namespace Convoy\Models\Objects\Server;

use Convoy\Models\Objects\Server\Configuration\ServerConfigObject;
use Convoy\Models\Objects\Server\Limits\ServerLimitsObject;
use Spatie\LaravelData\Data;

class ServerSpecificationsObject extends Data
{
    public function __construct(
        public ServerLimitsObject $limits,
        public ?ServerConfigObject $config,
    ) {
    }
}
