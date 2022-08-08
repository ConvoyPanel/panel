<?php

namespace App\Http\Controllers\Client\Servers;

use App\Http\Controllers\ApplicationApiController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Servers\Backups\RollbackBackupRequest;
use App\Http\Requests\Client\Servers\Backups\StoreBackupRequest;
use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxBackupRepository;
use App\Services\Servers\BackupService;
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
