<?php

namespace App\Http\Controllers\Client\Servers;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Services\Servers\BackupService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BackupController extends Controller
{
    public function __construct(private BackupService $backupService)
    {

    }

    public function index(Server $server)
    {
        return Inertia::render('servers/backups/Index', [
            'server' => $server,
            'backups' => $this->backupService->setServer($server)->getBackups(),
        ]);
    }
}
