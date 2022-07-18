<?php

namespace App\Http\Controllers\Client\Servers;

use App\Http\Controllers\ApplicationApiController;
use App\Http\Controllers\Controller;
use App\Models\Server;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Requests\Client\Servers\Settings\UpdateBasicInfoRequest;

class SettingsController extends ApplicationApiController
{
    public function index(Server $server)
    {
        return Inertia::render('servers/settings/Index', [
            'server' => $server,
        ]);
    }

    public function updateBasicInfo(Server $server, UpdateBasicInfoRequest $request)
    {
        $server->update(['name' => $request->name]);

        return $this->returnNoContent();
    }
}
