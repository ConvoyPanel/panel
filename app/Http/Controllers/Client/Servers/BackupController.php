<?php

namespace App\Http\Controllers\Client\Servers;

use App\Data\PaginationMeta;
use App\Data\Server\Backup\BackupEloquentData;
use App\Enums\Audit\AuditEvent;
use App\Enums\Server\BackupCompressionType;
use App\Enums\Server\BackupMode;
use App\Facades\Audit;
use App\Http\Requests\Client\Servers\Backups\DeleteBackupRequest;
use App\Http\Requests\Client\Servers\Backups\RestoreBackupRequest;
use App\Http\Requests\Client\Servers\Backups\StoreBackupRequest;
use App\Models\Backup;
use App\Models\Server;
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
    ) {}

    public function index(Request $request, Server $server)
    {
        $backups = QueryBuilder::for(Backup::query())
            ->where('backups.server_id', $server->id)
            ->allowedFilters(['name'])
            ->defaultSort('-created_at')
            ->allowedSorts('created_at', 'completed_at')
            ->paginate(min($request->query('per_page') ?? 20, 50));

        // Both quota figures span every non-failed backup, not just the current
        // page, so they are aggregated separately from the paginator.
        return [
            ...PaginationMeta::paginate($backups, BackupEloquentData::class),
            'backupCount' => $server->backups()->nonFailed()->count(),
            'backupSize' => $server->nonFailedBackupSize(),
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
                isLocked: $request->boolean('is_locked'),
            );

        // Subject is the server, not the backup: the feed people read is the server's, and a
        // backup that is later deleted would take its own history with it.
        Audit::record(
            AuditEvent::SERVER_BACKUP_CREATED,
            subject: $server,
            properties: [
                'backup' => $backup->name,
                'backup_uuid' => $backup->uuid,
                'mode' => $backup->mode,
                'is_locked' => $backup->is_locked,
            ],
        );

        return BackupEloquentData::from($backup);
    }

    public function restore(RestoreBackupRequest $request, Server $server, Backup $backup)
    {
        $this->restoreFromBackupService->handle($server, $backup);

        Audit::record(
            AuditEvent::SERVER_BACKUP_RESTORED,
            subject: $server,
            properties: ['backup' => $backup->name, 'backup_uuid' => $backup->uuid],
        );

        return response()->noContent();
    }

    public function destroy(DeleteBackupRequest $request, Server $server, Backup $backup)
    {
        // Read before the delete: afterwards the model's attributes are all that is left of it.
        $properties = ['backup' => $backup->name, 'backup_uuid' => $backup->uuid];

        $this->backupDeletionService->handle($backup);

        Audit::record(AuditEvent::SERVER_BACKUP_DELETED, subject: $server, properties: $properties);

        return response()->noContent();
    }
}
