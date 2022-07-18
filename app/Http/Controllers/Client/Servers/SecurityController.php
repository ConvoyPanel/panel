<?php

namespace App\Http\Controllers\Client\Servers;

use App\Http\Controllers\ApplicationApiController;
use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Services\Servers\CloudinitService;

class SecurityController extends ApplicationApiController
{
    public function __construct(private CloudinitService $cloudinitService)
    {
    }

    public function index(Server $server)
    {
        return inertia('servers/security/Index', [
            'server' => $server,
            'config' => $this->removeExtraDataProperty($this->cloudinitService->setServer($server)->fetchConfig()),
        ]);
    }
}