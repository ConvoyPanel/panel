<?php

namespace Convoy\Services\Servers\Backups;

use Convoy\Exceptions\Service\Backup\TooManyBackupsException;
use Convoy\Jobs\Server\MonitorBackupJob;
use Convoy\Models\Server;
use Convoy\Repositories\Eloquent\BackupRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxBackupRepository;
use Illuminate\Database\ConnectionInterface;
use Ramsey\Uuid\Uuid;

class BackupCreationService
{
    public function __construct(private ConnectionInterface $connection, private ProxmoxBackupRepository $proxmoxRepository, private BackupRepository $eloquentRepository)
    {
    }

    private bool $isLocked = false;

    public function setIsLocked(bool $isLocked): self
    {
        $this->isLocked = $isLocked;

        return $this;
    }

    public function handle(Server $server, string $name, string $mode, string $compressionType): ?string
    {
        $successful = $this->eloquentRepository->getNonFailedBackups($server);
        if (! $server->backup_limit || $successful->count() >= $server->backup_limit) {
            if ((int) $server->backup_limit <= 0 && isset($server->backup_limit)) {
                throw new TooManyBackupsException((int) $server->backup_limit);
            }
        }


        return $this->connection->transaction(function () use ($server, $name, $mode, $compressionType) {
            $backup = $this->eloquentRepository->create([
                'uuid' => Uuid::uuid4()->toString(),
                'server_id' => $server->id,
                'name' => $name,
                'locked' => $this->isLocked,
            ]);

            $upid = $this->proxmoxRepository->setServer($server)->backup($mode, $compressionType);

            MonitorBackupJob::dispatch($backup->id, $upid);

            return $backup;
        });
    }
}
