<?php

namespace App\Data\Server\Deployments;

use App\Models\Deployment;
use Spatie\LaravelData\Data;

class ServerDeploymentData extends Data
{
    public function __construct(
        public ?Deployment $deployment,
        public string $account_password,
    ) {}
}
