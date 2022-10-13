<?php

namespace Convoy\Http\Controllers\Client\Servers;

use Activity;
use Convoy\Enums\Servers\Status;
use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Http\Requests\Client\Servers\Settings\ReinstallServerRequest;
use Convoy\Http\Requests\Client\Servers\Settings\UpdateBasicInfoRequest;
use Convoy\Jobs\Servers\ProcessRebuild;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxCloudinitRepository;
use Convoy\Services\Activity\ActivityLogBatchService;
use Convoy\Services\Nodes\TemplateService;
use Inertia\Inertia;

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

    public function rebuild(Server $server, ReinstallServerRequest $request)
    {
        $this->batch->transaction(function (string $uuid) use ($server, $request) {
            $server->update(['status' => Status::INSTALLING->value]);

            $activity = Activity::event('server:rebuild')->runner()->log();

            ProcessRebuild::dispatch($server->id, $request->template_id, $uuid, $activity->id);
        });

        return redirect()->route('servers.show.building', [$server->id]);
    }
}
