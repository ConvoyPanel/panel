<?php

namespace App\Http\Controllers\Client\Servers;

use App\Http\Controllers\ApplicationApiController;
use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Services\Servers\CloudinitService;
use App\Services\Servers\VNCService;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class SecurityController extends ApplicationApiController
{
    public function __construct(private CloudinitService $cloudinitService, private VNCService $vncService)
    {
    }

    public function index(Server $server)
    {
        $data = $this->cloudinitService->setServer($server)->fetchConfig();
        return inertia('servers/security/Index', [
            'server' => $server,
            'config' => $this->removeExtraDataProperty($data ? $data : []),
        ]);
    }

    public function showVnc(Server $server)
    {
        return Inertia::render('servers/security/novnc/Index', [
            'server' => $server,
        ]);
    }

    public function getVncCredentials(Server $server)
    {
        $data = $this->vncService->setServer($server)->getSessionCredentials();

        if (!$data)
        {
            throw new ServiceUnavailableHttpException();
        }



        return array_merge($this->removeExtraDataProperty($data), ['endpoint' => $this->vncService->setServer($server)->getSessionEndpoint()]);
    }
}