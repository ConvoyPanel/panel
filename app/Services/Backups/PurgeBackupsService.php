<?php

namespace App\Services\Backups;

use App\Models\Backup;
use App\Models\Server;
use App\Repositories\Eloquent\BackupRepository;

class PurgeBackupsService
{
    public function __construct(
        private BackupRepository      $backupRepository,
        private BackupDeletionService $backupDeletionService,
    ) {
    }

    public function handle(Server $server)
    {
        $backups = $this->backupRepository->getNonFailedBackups($server)->get();

        $backups->each(function (Backup $backup) {
            $this->backupDeletionService->handle($backup);
        });
    }
}
