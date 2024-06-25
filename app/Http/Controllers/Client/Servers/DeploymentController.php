<?php

namespace Convoy\Http\Controllers\Client\Servers;

use Convoy\Http\Controllers\Controller;
use Convoy\Models\Server;
use Convoy\Transformers\Client\DeploymentTransformer;

class DeploymentController extends Controller
{
    public function index(Server $server)
    {
        $deployment = $server->deployments()->firstOrFail();

        return fractal($deployment, new DeploymentTransformer())->respond();
    }
}
