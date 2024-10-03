<?php

namespace Convoy\Http\Controllers\Client\Servers;

use Convoy\Http\Controllers\Controller;
use Convoy\Models\Server;
use Convoy\Repositories\Eloquent\SnapshotRepository;

class SnapshotController extends Controller
{
    public function __construct(private SnapshotRepository $repository)
    {
    }

    public function index(Server $server)
    {
        $snapshots = $this->repository->buildSnapshotTree($server->snapshots);

        return $snapshots;
    }
}
