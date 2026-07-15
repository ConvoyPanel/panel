<?php

namespace App\Services\Backups;

use App\Exceptions\Service\Backup\BackupLockedException;
use App\Models\Backup;
use App\Services\Proxmox\Server\ProxmoxBackupClient;
use Illuminate\Database\ConnectionInterface;

class BackupDeletionService
{
    public function __construct(
        private ConnectionInterface $connection,
        private ProxmoxBackupClient $proxmoxClient,
    ) {
    }

    public function handle(Backup $backup)
    {
        if ($backup->is_locked && is_null($backup->error_code) && ! is_null($backup->completed_at)) {
            throw new BackupLockedException();
        }

        $this->connection->transaction(function () use ($backup) {
            $this->proxmoxClient->setServer($backup->server)->delete($backup);

            $backup->delete();
        });
    }
}
