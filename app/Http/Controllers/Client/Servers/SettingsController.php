<?php

namespace Convoy\Http\Controllers\Client\Servers;

use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Http\Requests\Client\Servers\Settings\RenameServerRequest;
use Convoy\Models\Server;
use Convoy\Services\Servers\CloudinitService;

class SettingsController extends ApplicationApiController
{
    public function __construct(private CloudinitService $cloudinitService)
    {

    }

    public function rename(RenameServerRequest $request, Server $server)
    {
        $server->update($request->validated());

        $this->cloudinitService->updateHostname($server, $request->hostname);

        return $this->returnNoContent();
    }
}
