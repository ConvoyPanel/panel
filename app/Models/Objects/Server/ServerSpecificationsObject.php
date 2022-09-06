<?php

namespace App\Models\Objects\Server;

use App\Models\Objects\Server\Configuration\ServerConfigurationObject;
use App\Models\Objects\Server\Limits\ServerLimitsObject;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class ServerSpecificationsObject extends Data
{
    public function __construct(
        public ServerLimitsObject $limits,
        public ServerConfigurationObject $configuration,
    ) {
    }
}