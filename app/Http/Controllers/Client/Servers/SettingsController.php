<?php

namespace Convoy\Http\Controllers\Client\Servers;

use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Http\Requests\Client\Servers\Settings\RenameServerRequest;
use Convoy\Http\Requests\Client\Servers\Settings\UpdateNetworkRequest;
use Convoy\Models\Server;
use Convoy\Services\Servers\CloudinitService;
use Convoy\Transformers\Client\ServerNetworkTransformer;

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

    public function getNetwork(Server $server)
    {
        return fractal()->item([
            'nameservers' => $this->cloudinitService->getNameservers($server),
        ], new ServerNetworkTransformer())->respond();
    }

    public function updateNetwork(UpdateNetworkRequest $request, Server $server)
    {
        $this->cloudinitService->updateNameservers($server, $request->nameservers);

        return $this->returnNoContent();
    }
}
