<?php

namespace App\Http\Controllers\Client\Servers;

use App\Http\Controllers\ApplicationApiController;
use App\Http\Requests\Client\Servers\Settings\ReinstallServerRequest;
use App\Models\Server;
use Inertia\Inertia;
use App\Http\Requests\Client\Servers\Settings\UpdateBasicInfoRequest;
use App\Jobs\Servers\ProcessReinstallation;
use App\Repositories\Proxmox\Server\ProxmoxCloudinitRepository;
use App\Services\Activity\ActivityLogBatchService;
use App\Services\Nodes\TemplateService;

class SettingsController extends ApplicationApiController
{
    public function __construct(private TemplateService $templateService, private ProxmoxCloudinitRepository $repository, private ActivityLogBatchService $batch)
    {
    }

    public function index(Server $server)
    {
        return Inertia::render('servers/settings/Index', [
            'server' => $server,
            'config' => $this->repository->setServer($server)->getConfig(),
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
        $this->batch->transaction(function (string $uuid) use ($server, $request) {
            $server->update(['installing' => true]);

            ProcessReinstallation::dispatch($server->id, $request->template_id, $uuid);
        });

        return redirect()->route('servers.show.installing', [$server->id]);
    }
}
