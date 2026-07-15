<?php

namespace App\Services\Backups;

use App\Enums\Server\State;
use App\Enums\Server\ServerStatus;
use App\Jobs\Server\MonitorBackupRestorationJob;
use App\Models\Backup;
use App\Models\Server;
use App\Services\Proxmox\Server\ProxmoxBackupClient;
use App\Services\Proxmox\Server\ProxmoxServerClient;
use Illuminate\Database\ConnectionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class RestoreFromBackupService
{
    public function __construct(
        private ConnectionInterface $connection,
        private ProxmoxServerClient $serverClient,
        private ProxmoxBackupClient $proxmoxClient,
    ) {
    }

    public function handle(Server $server, Backup $backup)
    {
        if (! $server->status->isReady()) {
            throw new BadRequestHttpException(
                'This server is not currently in a state that allows for a backup to be restored.',
            );
        }

        $stateData = $this->serverClient->setServer($server)->getState();
        if ($stateData->state !== State::STOPPED) {
            throw new BadRequestHttpException(
                'The server needs to be stopped before a backup can be restored.',
            );
        }

        if ($backup->error_code !== null || $backup->completed_at === null) {
            throw new BadRequestHttpException(
                'This backup cannot be restored at this time: not completed or failed.',
            );
        }

        $this->connection->transaction(function () use ($server, $backup) {
            $server->update([
                'status' => ServerStatus::RESTORING_BACKUP->value,
            ]);

            $upid = $this->proxmoxClient->setServer($server)->restore($backup);

            MonitorBackupRestorationJob::dispatch($server, $upid);
        });
    }
}
