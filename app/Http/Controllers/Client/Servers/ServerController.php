<?php

namespace App\Http\Controllers\Client\Servers;

use App\Enums\Activity\Status;
use App\Http\Controllers\ApplicationApiController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Servers\UpdateBasicInfoRequest;
use App\Models\ActivityLog;
use App\Models\Server;
use App\Services\Servers\ResourceService;
use App\Services\Servers\ServerDetailService;
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
