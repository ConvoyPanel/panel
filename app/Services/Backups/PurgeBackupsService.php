<?php

namespace App\Services\Backups;

use App\Models\Backup;
use App\Models\Server;

class PurgeBackupsService
{
    public function __construct(
        private BackupDeletionService $backupDeletionService,
    ) {
    }

    public function handle(Server $server)
    {
        $backups = $server->backups()->nonFailed()->get();

        $backups->each(function (Backup $backup) {
            $this->backupDeletionService->handle($backup);
        });
    }
}
