<?php

namespace Convoy\Services\Backups;

use Carbon\CarbonImmutable;
use Convoy\Enums\Server\BackupCompressionType;
use Convoy\Enums\Server\BackupMode;
use Convoy\Exceptions\Service\Backup\TooManyBackupsException;
use Convoy\Jobs\Server\MonitorBackupJob;
use Convoy\Models\Backup;
use Convoy\Models\Server;
use Convoy\Repositories\Eloquent\BackupRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxBackupRepository;
use Illuminate\Database\ConnectionInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class BackupCreationService
{
    public function __construct(
        private ConnectionInterface $connection, private ProxmoxBackupRepository $proxmoxRepository,
        private BackupRepository $eloquentRepository,
    ) {
    }

    public function create(
        Server $server, string $name, BackupMode $mode, BackupCompressionType $compressionType,
        ?bool $isLocked = false,
    ): ?Backup {
        $limit = config('backups.throttles.limit');
        $period = config('backups.throttles.period');
        if ($period > 0) {
            $previous = $this->eloquentRepository->getBackupsGeneratedDuringTimespan(
                $server->id, $period,
            );
            if ($previous->count() >= $limit) {
                $message = sprintf(
                    'Only %d backups may be generated within a %d second span of time.', $limit,
                    $period,
                );

                throw new TooManyRequestsHttpException(
                    CarbonImmutable::now()->diffInSeconds(
                        $previous->last()->created_at->addSeconds($period),
                    ), $message,
                );
            }
        }

        $successful = $this->eloquentRepository->getNonFailedBackups($server);
        if (! $server->backup_limit || $successful->count() >= $server->backup_limit) {
            if (isset($server->backup_limit)) {
                throw new TooManyBackupsException((int) $server->backup_limit);
            }
        }

        return $this->connection->transaction(
            function () use ($server, $name, $mode, $compressionType, $isLocked) {
                $backup = $this->eloquentRepository->create([
                    'uuid' => Uuid::uuid4()->toString(),
                    'server_id' => $server->id,
                    'name' => $name,
                    'is_locked' => $isLocked,
                ]);

                $upid = $this->proxmoxRepository->setServer($server)->backup(
                    $mode, $compressionType,
                );

                MonitorBackupJob::dispatch($backup->id, $upid);

                return $backup;
            },
        );
    }
}
