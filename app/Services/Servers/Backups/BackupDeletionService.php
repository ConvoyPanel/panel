<?php

namespace Convoy\Services\Servers\Backups;

use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Convoy\Models\Backup;
use Convoy\Repositories\Proxmox\Server\ProxmoxBackupRepository;
use Convoy\Services\ProxmoxService;
use Illuminate\Database\ConnectionInterface;

class BackupDeletionService extends ProxmoxService
{
    public function __construct(private ConnectionInterface $connection, private ProxmoxBackupRepository $repository)
    {
    }

    public function handle(Backup $backup, ?bool $force = false)
    {
        $this->connection->transaction(function () use ($backup, $force) {
            try {
                $this->repository->setServer($backup->server)->delete($backup);
            } catch (ProxmoxConnectionException $exception) {
                if (!$force) {
                    throw $exception;
                }
            }

            $backup->delete();
        });
    }
}
