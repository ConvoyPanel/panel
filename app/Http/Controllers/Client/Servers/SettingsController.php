<?php

namespace App\Http\Controllers\Client\Servers;

use App\Http\Controllers\ApplicationApiController;
use App\Http\Controllers\Controller;
use App\Models\Server;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends ApplicationApiController
{
    public function index(Server $server)
    {
        return Inertia::render('servers/settings/Index', [
            'server' => $server,
        ]);
    }
}
