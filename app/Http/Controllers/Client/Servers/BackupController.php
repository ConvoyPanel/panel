<?php

namespace Convoy\Http\Controllers\Client\Servers;

use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Http\Requests\Client\Servers\Backups\RollbackBackupRequest;
use Convoy\Http\Requests\Client\Servers\Backups\StoreBackupRequest;
use Convoy\Models\Backup;
use Convoy\Models\Server;
use Convoy\Repositories\Eloquent\BackupRepository;
use Convoy\Services\Servers\Backups\BackupCreationService;
use Convoy\Transformers\Client\BackupTransformer;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class BackupController extends ApplicationApiController
{
    public function __construct(private BackupCreationService $creationService, private BackupRepository $repository)
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
            'backup_count' => $this->repository->getNonFailedBackups($server)->count(),
        ])->respond();
    }

    public function store(Server $server, StoreBackupRequest $request)
    {
        $backup = $this->creationService
            ->setIsLocked($request->input('locked', false))
            ->handle($server, $request->name, $request->mode, $request->compression_type);

        return fractal($backup, new BackupTransformer)->respond();
    }

    public function restore(Server $server, RollbackBackupRequest $request)
    {
    }

    public function destroy(Server $server, RollbackBackupRequest $request)
    {
    }
}
