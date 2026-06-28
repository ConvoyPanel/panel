<?php

namespace App\Http\Controllers\Client\Servers;

use App\Data\Server\Deployments\DeploymentData;
use App\Models\Server;

class DeploymentController
{
    public function index(Server $server)
    {
        $deployment = $server->deployments()->with(['template', 'steps'])->firstOrFail();

        return DeploymentData::from($deployment)->include('template', 'steps');
    }
}
