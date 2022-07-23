<?php

namespace App\Http\Controllers\Client\Servers;

use App\Http\Controllers\ApplicationApiController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Servers\Backups\RollbackBackupRequest;
use App\Http\Requests\Client\Servers\Backups\StoreBackupRequest;
use App\Models\Server;
use App\Services\Servers\BackupService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BackupController extends ApplicationApiController
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

    public function createBackup(Server $server, StoreBackupRequest $request)
    {
        return $this->returnInertiaResponse($request, 'backup-created', $this->backupService->setServer($server)->createBackup($request->mode, $request->compression));
    }

    public function rollback(Server $server, RollbackBackupRequest $request)
    {
        return $this->backupService->setServer($server)->rollback($request->archive);
    }
}
