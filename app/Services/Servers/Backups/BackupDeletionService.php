<?php

namespace Convoy\Services\Servers\Backups;

use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Convoy\Models\Backup;
use Convoy\Repositories\Proxmox\Server\ProxmoxBackupRepository;
use Convoy\Services\ProxmoxService;
use Illuminate\Database\ConnectionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BackupDeletionService extends ProxmoxService
{
    public function __construct(private ConnectionInterface $connection, private ProxmoxBackupRepository $repository)
    {
    }

    public function handle(Backup $backup, ?bool $force = false)
    {
        if (is_null($backup->completed_at)) {
            throw new BadRequestHttpException('This backup cannot be restored at this time: not completed.');
        }

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
