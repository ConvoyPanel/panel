<?php

namespace Convoy\Http\Controllers\Admin\Servers\Settings;

use Convoy\Enums\Server\Status;
use Convoy\Enums\Server\SuspensionAction;
use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Http\Requests\Admin\Servers\Settings\UpdateBasicInfoRequest;
use Convoy\Http\Requests\Admin\Servers\Settings\UpdateDetailsRequest;
use Convoy\Models\Server;
use Convoy\Models\Template;
use Convoy\Services\Servers\ServerDetailService;
use Convoy\Services\Servers\SuspensionService;
use Inertia\Inertia;

class SettingsController extends ApplicationApiController
{
    public function __construct(private ServerDetailService $detailService, private SuspensionService $suspensionService)
    {}

    public function index(Server $server)
    {
        return Inertia::render('admin/servers/settings/Index', [
            'server' => $server->load('template'),
            'details' => $this->detailService->setServer($server)->getDetails(),
        ]);
    }

    public function updateDetails(Server $server, UpdateDetailsRequest $request)
    {
        $server->update($request->validated());

        return back();
    }

    public function updateBasicInfo(Server $server, UpdateBasicInfoRequest $request)
    {
        if ($request->template === true) {
            if (isset($server->template)) {
                $server->template->update(['visible' => $request->visible ? true : false]);
            } else {
                Template::create([
                    'server_id' => $server->id,
                    'visible' => $request->visible ? true : false,
                ]);
            }
        } elseif ($request->template === false) { //strict type checking
            if (isset($server->template)) {
                $server->template->delete();
            }
        }

        $server->update($request->validated());

        return back();
    }

    public function toggleSuspension(Server $server)
    {
        $this->suspensionService->setServer($server)->toggle($server->status === Status::SUSPENDED->value ? SuspensionAction::UNSUSPEND : SuspensionAction::SUSPEND);

        return back();
    }
}
