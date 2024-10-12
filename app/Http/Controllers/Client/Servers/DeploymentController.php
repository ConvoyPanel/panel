<?php

namespace App\Http\Controllers\Client\Servers;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Transformers\Client\DeploymentTransformer;

class DeploymentController extends Controller
{
    public function index(Server $server)
    {
        $deployment = $server->deployments()->firstOrFail();

        return fractal($deployment, new DeploymentTransformer())->respond();
    }
}
