<?php

namespace App\Http\Controllers\Client\Servers;

use App\Http\Controllers\ApplicationApiController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Servers\UpdateBasicInfoRequest;
use App\Models\Server;
use App\Services\Servers\ResourceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServerController extends ApplicationApiController
{
    public function show(Server $server)
    {
        return Inertia::render('servers/Show', [
            'server' => $server->toArray(),
        ]);
    }
}
