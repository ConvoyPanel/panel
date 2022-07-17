<?php

namespace App\Http\Controllers\Client\Servers;

use App\Http\Controllers\Controller;
use App\Models\Server;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BackupController extends Controller
{
    public function index(Server $server)
    {
        return Inertia::render('servers/backups/Index', [
            'server' => $server
        ]);
    }
}
