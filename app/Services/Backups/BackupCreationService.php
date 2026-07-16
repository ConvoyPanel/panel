<?php

namespace App\Services\Backups;

use App\Enums\Server\BackupCompressionType;
use App\Enums\Server\BackupMode;
use App\Exceptions\Service\Backup\TooManyBackupsException;
use App\Jobs\Server\MonitorBackupJob;
use App\Models\Backup;
use App\Models\Server;
use App\Services\Proxmox\Server\ProxmoxBackupClient;
use Carbon\CarbonImmutable;
use Illuminate\Database\ConnectionInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class BackupCreationService
{
    public function __construct(
        private ConnectionInterface $connection,
        private ProxmoxBackupClient $proxmoxClient,
    ) {}

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
            $previous = $server->backups()
                ->createdWithinSeconds($period)
                ->latest('created_at')
                ->get();
            if ($previous->count() >= $limit) {
                $message = sprintf(
                    'Only %d backups may be generated within a %d second span of time.',
                    $limit,
                    $period,
                );

                throw new TooManyRequestsHttpException(
                    (int) CarbonImmutable::now()->diffInSeconds(
                        $previous->last()->created_at->addSeconds($period),
                    ),
                    $message,
                );
            }
        }

        $successful = $server->backups()->nonFailed();
        if ($server->backup_count_limit >= 0 && $successful->count() >= $server->backup_count_limit) {
            throw new TooManyBackupsException($server->backup_count_limit);
        }

        $storage = $server->node->backupStorage();
        if (is_null($storage)) {
            throw new ConflictHttpException('No backup-capable storage is configured for this node.');
        }

        return $this->connection->transaction(
            function () use ($server, $name, $mode, $compressionType, $isLocked, $storage) {
                $backup = Backup::create([
                    'uuid' => Uuid::uuid4()->toString(),
                    'server_id' => $server->id,
                    'storage_id' => $storage->id,
                    'name' => $name,
                    'is_locked' => $isLocked,
                    'size' => 0,
                ]);

                $upid = $this->proxmoxClient->setServer($server)->backup(
                    $mode,
                    $compressionType,
                    $storage->name,
                );

                MonitorBackupJob::dispatch($backup, $upid);

                return $backup;
            },
        );
    }
}
