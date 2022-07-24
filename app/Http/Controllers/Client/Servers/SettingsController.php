<?php

namespace App\Http\Controllers\Client\Servers;

use App\Http\Controllers\ApplicationApiController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Servers\Settings\ReinstallServerRequest;
use App\Models\Server;
use Illuminate\Http\Request;
use App\Services\Servers\CloudinitService;
use Inertia\Inertia;
use App\Http\Requests\Client\Servers\Settings\UpdateBasicInfoRequest;
use App\Services\Servers\InstallService;
use App\Services\Servers\VNCService;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class SettingsController extends ApplicationApiController
{
    public function __construct(private CloudinitService $cloudinitService, private InstallService $installService)
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

    public function getTemplates()
    {
        return Server::where(['is_template' => true, 'make_template_visible' => true])->get(['id', 'vmid', 'name']);
    }

    public function reinstall(Server $server, ReinstallServerRequest $request)
    {
        if (!$server->is_template)
        {
            throw new UnauthorizedHttpException('', 'template_id not marked as template server');
        }

        $this->installService->setServer($server)->reinstall(Server::find($request->template_id));

        return $this->returnNoContent();
    }
}
