<?php

namespace App\Services\Backups;

use App\Exceptions\Service\Backup\BackupLockedException;
use App\Models\Backup;
use App\Repositories\Proxmox\Server\ProxmoxBackupRepository;
use Illuminate\Database\ConnectionInterface;

class BackupDeletionService
{
    public function __construct(
        private ConnectionInterface     $connection,
        private ProxmoxBackupRepository $proxmoxRepository,
    ) {
    }

    public function handle(Backup $backup)
    {
        if ($backup->is_locked && ($backup->is_successful && !is_null($backup->completed_at))) {
            throw new BackupLockedException();
        }

        $this->connection->transaction(function () use ($backup) {
            $this->proxmoxRepository->setServer($backup->server)->delete($backup);

            $backup->delete();
        });
    }
}
