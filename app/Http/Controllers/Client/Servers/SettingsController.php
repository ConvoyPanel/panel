<?php

namespace Convoy\Http\Controllers\Client\Servers;

use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Http\Requests\Client\Servers\Settings\RenameServerRequest;
use Convoy\Models\Server;

class SettingsController extends ApplicationApiController
{
    public function __construct()
    {

    }

    public function rename(RenameServerRequest $request, Server $server)
    {
        $server->update($request->validated());

        return;
    }
}
