<?php

namespace App\Http\Controllers\Client\Servers;

use App\Http\Controllers\ApplicationApiController;
use App\Http\Controllers\Controller;
use App\Models\Server;
use Illuminate\Http\Request;
use App\Services\Servers\CloudinitService;
use Inertia\Inertia;
use App\Http\Requests\Client\Servers\Settings\UpdateBasicInfoRequest;

class SettingsController extends ApplicationApiController
{
    public function __construct(private CloudinitService $cloudinitService)
    {
    }

    public function index(Server $server)
    {
        $data = $this->cloudinitService->setServer($server)->fetchConfig();
        return Inertia::render('servers/settings/Index', [
            'server' => $server,
            'config' => $data ? $this->removeExtraDataProperty($data) : $this->cloudinitService->getServerInaccessibleConfig(),
        ]);
    }

    public function updateBasicInfo(Server $server, UpdateBasicInfoRequest $request)
    {
        $server->update(['name' => $request->name]);

        return $this->returnNoContent();
    }
}
