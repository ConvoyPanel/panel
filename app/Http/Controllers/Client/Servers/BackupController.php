<?php

namespace App\Http\Controllers\Client\Servers;

use App\Data\PaginationMeta;
use App\Data\Server\Backup\BackupEloquentData;
use App\Enums\Server\BackupCompressionType;
use App\Enums\Server\BackupMode;
use App\Http\Requests\Client\Servers\Backups\DeleteBackupRequest;
use App\Http\Requests\Client\Servers\Backups\RestoreBackupRequest;
use App\Http\Requests\Client\Servers\Backups\StoreBackupRequest;
use App\Models\Backup;
use App\Models\Server;
use App\Repositories\Eloquent\BackupRepository;
use App\Services\Backups\BackupCreationService;
use App\Services\Backups\BackupDeletionService;
use App\Services\Backups\RestoreFromBackupService;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class BackupController
{
    public function __construct(
        private BackupCreationService $backupCreationService,
        private BackupDeletionService $backupDeletionService,
        private RestoreFromBackupService $restoreFromBackupService,
        private BackupRepository $backupRepository,
    ) {}

    public function index(Request $request, Server $server)
    {
        $backups = QueryBuilder::for(Backup::query())
            ->where('backups.server_id', $server->id)
            ->allowedFilters(['name'])
            ->defaultSort('-created_at')
            ->allowedSorts('created_at', 'completed_at')
            ->paginate(min($request->query('per_page') ?? 20, 50));

        return [
            ...PaginationMeta::paginate($backups, BackupEloquentData::class),
            'backupCount' => $this->backupRepository->getNonFailedBackups($server)->count(),
        ];
    }

    public function store(StoreBackupRequest $request, Server $server)
    {
        $backup = $this->backupCreationService
            ->create(
                server: $server,
                name: $request->name,
                mode: $request->enum('mode', BackupMode::class),
                compressionType: $request->enum('compression_type', BackupCompressionType::class),
                isLocked: $request->input('locked', false),
            );

        return BackupEloquentData::from($backup);
    }

    public function restore(RestoreBackupRequest $request, Server $server, Backup $backup)
    {
        $this->restoreFromBackupService->handle($server, $backup);

        return response()->noContent();
    }

    public function destroy(DeleteBackupRequest $request, Server $server, Backup $backup)
    {
        $this->backupDeletionService->handle($backup);

        return response()->noContent();
    }
}
