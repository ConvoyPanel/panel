<?php

namespace Convoy\Http\Controllers\Client\Servers;

use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Http\Requests\Client\Servers\Backups\StoreBackupRequest;
use Convoy\Models\Backup;
use Convoy\Models\Server;
use Convoy\Repositories\Eloquent\BackupRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxBackupRepository;
use Convoy\Services\Servers\Backups\BackupCreationService;
use Convoy\Services\Servers\Backups\BackupDeletionService;
use Convoy\Services\Servers\ServerDetailService;
use Convoy\Transformers\Client\BackupTransformer;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class BackupController extends ApplicationApiController
{
    public function __construct(
        private BackupCreationService   $backupCreationService,
        private BackupDeletionService   $backupDeletionService,
        private BackupRepository        $backupRepository,
    )
    {
    }

    public function index(Server $server, Request $request)
    {
        $backups = QueryBuilder::for(Backup::query())
            ->where('backups.server_id', $server->id)
            ->allowedFilters(['name'])
            ->defaultSort('-created_at')
            ->allowedSorts('created_at', 'completed_at')
            ->paginate(min($request->query('per_page') ?? 20, 50));

        return fractal($backups, new BackupTransformer)->addMeta([
            'backup_count' => $this->backupRepository->getNonFailedBackups($server)->count(),
        ])->respond();
    }

    public function store(Server $server, StoreBackupRequest $request)
    {
        $backup = $this->backupCreationService
            ->create($server, $request->name, $request->mode, $request->compression_type, $request->input('locked', false));

        return fractal($backup, new BackupTransformer)->respond();
    }

    public function restore(Server $server, Backup $backup)
    {
        $this->backupService->restore($server, $backup);

        return $this->returnNoContent();
    }

    public function destroy(Server $server, Backup $backup)
    {
        $this->backupDeletionService->handle($backup);

        return $this->returnNoContent();
    }
}
