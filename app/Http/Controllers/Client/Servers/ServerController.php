<?php

namespace Convoy\Http\Controllers\Client\Servers;

use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxServerRepository;
use Convoy\Services\Servers\ServerDetailService;
use Convoy\Transformers\Client\ServerStatusTransformer;
use Convoy\Transformers\Client\ServerTransformer;

class ServerController extends ApplicationApiController
{
    public function __construct(private ServerDetailService $detailService, private ProxmoxServerRepository $repository)
    {
    }

    public function index(Server $server)
    {
        return fractal($this->detailService->getByEloquent($server), new ServerTransformer)->respond();
    }

    public function status(Server $server)
    {
        return fractal()->item($this->repository->setServer($server)->getStatus(), new ServerStatusTransformer)->respond();
    }
}
