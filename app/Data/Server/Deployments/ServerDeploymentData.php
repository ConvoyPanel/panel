<?php

namespace Convoy\Data\Server\Deployments;

use Convoy\Models\Server;
use Convoy\Models\Template;
use Spatie\LaravelData\Data;

class ServerDeploymentData extends Data
{
    public function __construct(
        public Server    $server,
        public ?Template $template,
        public string    $account_password,
        public bool      $should_create_server,
        public bool      $start_on_completion,
    )
    {
    }
}
