<?php

namespace App\Services\Backups;

use App\Enums\Server\BackupCompressionType;
use App\Enums\Server\BackupMode;
use App\Exceptions\Service\Backup\TooManyBackupsException;
use App\Jobs\Server\MonitorBackupJob;
use App\Models\Backup;
use App\Models\Server;
use App\Repositories\Eloquent\BackupRepository;
use App\Repositories\Proxmox\Server\ProxmoxBackupRepository;
use Carbon\CarbonImmutable;
use Illuminate\Database\ConnectionInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class BackupCreationService
{
    public function __construct(
        private ConnectionInterface $connection,
        private ProxmoxBackupRepository $proxmoxRepository,
        private BackupRepository $eloquentRepository,
    ) {
    }

    public function create(
        Server $server,
        string $name,
        BackupMode $mode,
        BackupCompressionType $compressionType,
        ?bool $isLocked = false,
    ): ?Backup {
        $limit = config('backups.throttles.limit');
        $period = config('backups.throttles.period');
        if ($period > 0) {
            $previous = $this->eloquentRepository->getBackupsGeneratedDuringTimespan(
                $server->id,
                $period,
            );
            if ($previous->count() >= $limit) {
                $message = sprintf(
                    'Only %d backups may be generated within a %d second span of time.',
                    $limit,
                    $period,
                );

                throw new TooManyRequestsHttpException(
                    CarbonImmutable::now()->diffInSeconds(
                        $previous->last()->created_at->addSeconds($period),
                    ),
                    $message,
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
                    $mode,
                    $compressionType,
                );

                MonitorBackupJob::dispatch($backup->id, $upid);

                return $backup;
            },
        );
    }
}
