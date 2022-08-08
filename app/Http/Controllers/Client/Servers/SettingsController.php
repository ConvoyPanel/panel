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
use App\Jobs\Servers\ProcessReinstallation;
use App\Models\Node;
use App\Models\Template;
use App\Services\Nodes\TemplateService;
use App\Services\Servers\InstallService;
use App\Services\Servers\VncService;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class SettingsController extends ApplicationApiController
{
    public function __construct(private TemplateService $templateService, private CloudinitService $cloudinitService, private InstallService $installService)
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

    public function getTemplates(Server $server)
    {
        return $this->templateService->setServer($server)->getTemplates(true);
    }

    public function reinstall(Server $server, ReinstallServerRequest $request)
    {
        $server->update(['installing' => true]);

        ProcessReinstallation::dispatch($server->id, $request->template_id);

        return redirect()->route('servers.show.installing', [$server->id]);
    }
}
