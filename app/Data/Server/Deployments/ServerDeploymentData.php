<?php

namespace App\Data\Server\Deployments;

use App\Models\Server;
use App\Models\Template;
use Spatie\LaravelData\Data;

class ServerDeploymentData extends Data
{
    public function __construct(
        public Server $server,
        public ?Template $template,
        public string $account_password,
        public bool $should_create_server,
        public bool $start_on_completion,
    ) {
    }
}
