<?php

namespace Convoy\Services\Servers\Backups;

use Carbon\CarbonImmutable;
use Convoy\Enums\Server\Status;
use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Convoy\Exceptions\Service\Backup\TooManyBackupsException;
use Convoy\Jobs\Server\MonitorBackupJob;
use Convoy\Jobs\Server\MonitorBackupRestorationJob;
use Convoy\Models\Backup;
use Convoy\Models\Server;
use Convoy\Repositories\Eloquent\BackupRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxBackupRepository;
use Convoy\Services\Servers\ServerDetailService;
use Illuminate\Database\ConnectionInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class BackupService
{
    public function __construct(private ConnectionInterface $connection, private ServerDetailService $detailService, private ProxmoxBackupRepository $proxmoxRepository, private BackupRepository $eloquentRepository)
    {
    }

    public function create(Server $server, string $name, string $mode, string $compressionType, ?bool $isLocked = false): ?Backup
    {
        $limit = config('backups.throttles.limit');
        $period = config('backups.throttles.period');
        if ($period > 0) {
            $previous = $this->eloquentRepository->getBackupsGeneratedDuringTimespan($server->id, $period);
            if ($previous->count() >= $limit) {
                $message = sprintf('Only %d backups may be generated within a %d second span of time.', $limit, $period);

                throw new TooManyRequestsHttpException(CarbonImmutable::now()->diffInSeconds($previous->last()->created_at->addSeconds($period)), $message);
            }
        }

        $successful = $this->eloquentRepository->getNonFailedBackups($server);
        if (! $server->backup_limit || $successful->count() >= $server->backup_limit) {
            if (isset($server->backup_limit)) {
                throw new TooManyBackupsException((int) $server->backup_limit);
            }
        }


        return $this->connection->transaction(function () use ($server, $name, $mode, $compressionType, $isLocked) {
            $backup = $this->eloquentRepository->create([
                'uuid' => Uuid::uuid4()->toString(),
                'server_id' => $server->id,
                'name' => $name,
                'is_locked' => $isLocked,
            ]);

            $upid = $this->proxmoxRepository->setServer($server)->backup($mode, $compressionType);

            MonitorBackupJob::dispatch($backup->id, $upid);

            return $backup;
        });
    }

    public function restore(Server $server, Backup $backup)
    {
        if (!is_null($server->status)) {
            throw new BadRequestHttpException('This server is not currently in a state that allows for a backup to be restored.');
        }

        $details = $this->detailService->getByProxmox($server);
        if ($details->state !== 'stopped') {
            throw new BadRequestHttpException('The server needs to be stopped before a backup can be restored.');
        }

        if (!$backup->successful && is_null($backup->completed_at)) {
            throw new BadRequestHttpException('This backup cannot be restored at this time: not completed or failed.');
        }

        $this->connection->transaction(function () use ($server, $backup) {
            $server->update([
                'status' => Status::RESTORING_BACKUP->value,
            ]);

            $upid = $this->proxmoxRepository->setServer($server)->restore($backup);

            MonitorBackupRestorationJob::dispatch($server->id, $upid);
        });
    }

    public function delete(Backup $backup)
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