<?php

namespace App\Data\Server\Deployments;

use App\Models\Server;
use App\Models\Template;
use App\Models\Deployment;
use Spatie\LaravelData\Data;

class ServerDeploymentData extends Data
{
    public function __construct(
        public ?Deployment $deployment,
        public string $account_password,
    ) {
    }
}
