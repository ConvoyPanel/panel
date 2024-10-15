<?php

namespace App\Services\Backups;

use App\Enums\Server\State;
use App\Enums\Server\Status;
use App\Jobs\Server\MonitorBackupRestorationJob;
use App\Models\Backup;
use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxBackupRepository;
use App\Repositories\Proxmox\Server\ProxmoxServerRepository;
use Illuminate\Database\ConnectionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class RestoreFromBackupService
{
    public function __construct(
        private ConnectionInterface $connection,
        private ProxmoxServerRepository $serverRepository,
        private ProxmoxBackupRepository $proxmoxRepository,
    ) {
    }

    public function handle(Server $server, Backup $backup)
    {
        if (! is_null($server->status)) {
            throw new BadRequestHttpException(
                'This server is not currently in a state that allows for a backup to be restored.',
            );
        }

        $stateData = $this->serverRepository->setServer($server)->getState();
        if ($stateData->state !== State::STOPPED) {
            throw new BadRequestHttpException(
                'The server needs to be stopped before a backup can be restored.',
            );
        }

        if (! $backup->successful && is_null($backup->completed_at)) {
            throw new BadRequestHttpException(
                'This backup cannot be restored at this time: not completed or failed.',
            );
        }

        $this->connection->transaction(function () use ($server, $backup) {
            $server->update([
                'status' => Status::RESTORING_BACKUP->value,
            ]);

            $upid = $this->proxmoxRepository->setServer($server)->restore($backup);

            MonitorBackupRestorationJob::dispatch($server->id, $upid);
        });
    }
}
