<?php

namespace Convoy\Http\Controllers\Client\Servers;

use Convoy\Enums\Activity\Status;
use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Http\Controllers\Controller;
use Convoy\Http\Requests\Client\Servers\UpdateBasicInfoRequest;
use Convoy\Models\ActivityLog;
use Convoy\Models\Server;
use Convoy\Services\Servers\ResourceService;
use Convoy\Services\Servers\ServerDetailService;
use Illuminate\Http\Request;
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
        $deployment = ActivityLog::where(['event' => 'server:rebuild', 'status' => Status::RUNNING])
            ->orWhere(['event' => 'server:build', 'status' => Status::RUNNING])->first();

        return Inertia::render('servers/Building', [
            'server' => $server->toArray(),
            'batch' => $deployment->batch,
            'batch_type' => $deployment->event,
        ]);
    }

    public function getDetails(Server $server)
    {
        return $this->detailService->setServer($server)->getDetails();
    }
}
