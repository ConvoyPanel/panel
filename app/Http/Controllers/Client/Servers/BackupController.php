<?php

namespace Convoy\Http\Controllers\Client\Servers;

use Convoy\Http\Requests\Client\Servers\Backups\DeleteBackupRequest;
use Convoy\Http\Requests\Client\Servers\Backups\RestoreBackupRequest;
use Convoy\Models\Backup;
use Convoy\Models\Server;
use Illuminate\Http\Request;
use Convoy\Enums\Server\BackupMode;
use Spatie\QueryBuilder\QueryBuilder;
use Convoy\Enums\Server\BackupCompressionType;
use Convoy\Transformers\Client\BackupTransformer;
use Convoy\Repositories\Eloquent\BackupRepository;
use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Services\Servers\Backups\BackupCreationService;
use Convoy\Services\Servers\Backups\BackupDeletionService;
use Convoy\Services\Servers\Backups\RestoreFromBackupService;
use Convoy\Http\Requests\Client\Servers\Backups\StoreBackupRequest;

class BackupController extends ApplicationApiController
{
    public function __construct(
        private BackupCreationService    $backupCreationService,
        private BackupDeletionService    $backupDeletionService,
        private RestoreFromBackupService $restoreFromBackupService,
        private BackupRepository         $backupRepository,
    )
    {
    }

    public function index(Request $request, Server $server)
    {
        $backups = QueryBuilder::for(Backup::query())
                               ->where('backups.server_id', $server->id)
                               ->allowedFilters(['name'])
                               ->defaultSort('-created_at')
                               ->allowedSorts('created_at', 'completed_at')
                               ->paginate(min($request->query('per_page') ?? 20, 50));

        return fractal($backups, new BackupTransformer())->addMeta([
            'backup_count' => $this->backupRepository->getNonFailedBackups($server)->count(),
        ])->respond();
    }

    public function store(StoreBackupRequest $request, Server $server)
    {
        $backup = $this->backupCreationService
            ->create(
                server         : $server,
                name           : $request->name,
                mode           : $request->enum('mode', BackupMode::class),
                compressionType: $request->enum('compression_type', BackupCompressionType::class),
                isLocked       : $request->input('locked', false),
            );

        return fractal($backup, new BackupTransformer())->respond();
    }

    public function restore(RestoreBackupRequest $request, Server $server, Backup $backup)
    {
        $this->restoreFromBackupService->handle($server, $backup);

        return $this->returnNoContent();
    }

    public function destroy(DeleteBackupRequest $request, Server $server, Backup $backup)
    {
        $this->backupDeletionService->handle($backup);

        return $this->returnNoContent();
    }
}
