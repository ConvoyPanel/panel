<?php

namespace Convoy\Http\Controllers\Client\Servers;

use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Http\Controllers\Controller;
use Convoy\Http\Requests\Client\Servers\Backups\RollbackBackupRequest;
use Convoy\Http\Requests\Client\Servers\Backups\StoreBackupRequest;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxBackupRepository;
use Convoy\Services\Servers\BackupService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BackupController extends ApplicationApiController
{
    public function __construct(private ProxmoxBackupRepository $repository)
    {

    }

    public function index(Server $server)
    {
        return Inertia::render('servers/backups/Index', [
            'server' => $server,
            'backups' => $this->repository->setServer($server)->getBackups(),
        ]);
    }

    public function store(Server $server, StoreBackupRequest $request)
    {
        $this->repository->setServer($server)->backup($request->mode, $request->compressionType);

        return back();
    }

    public function restore(Server $server, RollbackBackupRequest $request)
    {
        $this->repository->setServer($server)->restore($request->archive);

        return back();
    }

    public function destroy(Server $server, RollbackBackupRequest $request)
    {
        $this->repository->setServer($server)->delete($request->archive);

        return back();
    }
}
