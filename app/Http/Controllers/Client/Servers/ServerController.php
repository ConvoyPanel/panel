<?php

namespace App\Http\Controllers\Client\Servers;

use App\Http\Controllers\Controller;
use App\Models\Server;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServerController extends Controller
{
    public function show(Server $server)
    {
        return Inertia::render('servers/Show', [
            'server' => $server->toArray()
        ]);
    }
}
