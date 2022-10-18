<?php

namespace Convoy\Http\Controllers\Client\Servers;

use Convoy\Enums\Activity\Status;
use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Models\ActivityLog;
use Convoy\Models\Server;
use Convoy\Services\Servers\ServerDetailService;
use Inertia\Inertia;

class ServerController extends ApplicationApiController
{
    public function __construct(private ServerDetailService $detailService)
    {
    }

    public function show(Server $server)
    {
        return Inertia::render('servers/Show', [
            'server' => $server->toArray(),
        ]);
    }

    public function showBuilding(Server $server)
    {
        $deployment = $server->activity()->where([['event', '=', 'server:rebuild'], ['status', '=', Status::RUNNING->value]])
            ->orWhere([['event', '=', 'server:build'], ['status', '=', Status::RUNNING->value]])->first();

        return Inertia::render('servers/Building', [
            'server' => $server->toArray(),
            'batch' => $deployment?->batch,
            'batch_type' => (bool) $deployment?->batch ? $deployment->event : null,
            'events' => (bool) $deployment?->batch ? ActivityLog::where('batch', '=', $deployment->batch)->get() : [],
        ]);
    }

    public function showSuspended(Server $server)
    {
        return Inertia::render('servers/Suspended', [
            'server' => $server->toArray(),
        ]);
    }

    public function getDetails(Server $server)
    {
        return $this->detailService->setServer($server)->getDetails();
    }
}
