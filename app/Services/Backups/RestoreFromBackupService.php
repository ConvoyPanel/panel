<?php

namespace Convoy\Services\Backups;

use Convoy\Enums\Server\State;
use Convoy\Enums\Server\Status;
use Convoy\Jobs\Server\MonitorBackupRestorationJob;
use Convoy\Models\Backup;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxBackupRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxServerRepository;
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
