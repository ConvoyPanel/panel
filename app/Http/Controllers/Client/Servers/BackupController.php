<?php

namespace Convoy\Http\Controllers\Client\Servers;

use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Http\Requests\Client\Servers\Backups\RollbackBackupRequest;
use Convoy\Http\Requests\Client\Servers\Backups\StoreBackupRequest;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxBackupRepository;
use Convoy\Services\Servers\Backups\BackupCreationService;
use Convoy\Services\Servers\Backups\BackupDeletionService;
use Inertia\Inertia;

class BackupController extends ApplicationApiController
{
    public function __construct(protected ProxmoxBackupRepository $repository, protected BackupCreationService $creationService, protected BackupDeletionService $deletionService)
    {
    }

    public function index(Server $server)
    {
        $backups = $this->repository->setServer($server)->getBackups();

        return Inertia::render('servers/backups/Index', [
            'server' => $server,
            'backups' => $backups,
            'can_create' => isset($server->backup_limit) ? count($backups) < $server->backup_limit : true,
        ]);
    }

    public function store(Server $server, StoreBackupRequest $request)
    {
        $this->creationService->setServer($server)->handle($request->mode, $request->compressionType);

        return back();
    }

    public function restore(Server $server, RollbackBackupRequest $request)
    {
        $this->repository->setServer($server)->restore($request->archive);

        return back();
    }

    public function destroy(Server $server, RollbackBackupRequest $request)
    {
        $this->deletionService->setServer($server)->handle($request->archive);

        return back();
    }
}
