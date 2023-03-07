<?php

namespace Convoy\Services\Servers\Backups;

use Convoy\Models\Backup;
use Convoy\Repositories\Eloquent\BackupRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxBackupRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxServerRepository;
use Illuminate\Database\ConnectionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BackupDeletionService
{
    public function __construct(private ConnectionInterface $connection, private ProxmoxBackupRepository $proxmoxRepository)
    {
    }

    public function handle(Backup $backup)
    {
        if (is_null($backup->completed_at)) {
            throw new BadRequestHttpException('This backup cannot be restored at this time: not completed.');
        }

        $this->connection->transaction(function () use ($backup) {
            $this->proxmoxRepository->setServer($backup->server)->delete($backup);

            $backup->delete();
        });
    }
}