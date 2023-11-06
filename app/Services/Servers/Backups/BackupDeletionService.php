<?php

namespace Convoy\Services\Servers\Backups;

use Convoy\Models\Backup;
use Illuminate\Database\ConnectionInterface;
use Convoy\Exceptions\Service\Backup\BackupLockedException;
use Convoy\Repositories\Proxmox\Server\ProxmoxBackupRepository;

class BackupDeletionService
{
    public function __construct(private ConnectionInterface $connection, private ProxmoxBackupRepository $proxmoxRepository)
    {
    }

    public function handle(Backup $backup)
    {
        if ($backup->is_locked && ($backup->is_successful && ! is_null($backup->completed_at))) {
            throw new BackupLockedException();
        }

        $this->connection->transaction(function () use ($backup) {
            $this->proxmoxRepository->setServer($backup->server)->delete($backup);

            $backup->delete();
        });
    }
}
