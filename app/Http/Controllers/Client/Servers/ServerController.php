<?php

namespace Convoy\Http\Controllers\Client\Servers;

use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Models\Server;
use Convoy\Services\Servers\ServerDetailService;
use Convoy\Transformers\Client\ServerTransformer;

class ServerController extends ApplicationApiController
{
    public function __construct(private ServerDetailService $service)
    {
    }

    public function index(Server $server)
    {
        return fractal($this->service->getByEloquent($server), new ServerTransformer)->respond();
    }
}
