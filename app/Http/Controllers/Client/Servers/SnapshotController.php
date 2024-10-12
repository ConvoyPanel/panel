<?php

namespace App\Http\Controllers\Client\Servers;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Repositories\Eloquent\SnapshotRepository;

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
