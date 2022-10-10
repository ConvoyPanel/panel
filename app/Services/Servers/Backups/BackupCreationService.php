<?php

namespace Convoy\Services\Servers\Backups;

use Convoy\Exceptions\Service\Backup\TooManyBackupsException;
use Convoy\Repositories\Proxmox\Server\ProxmoxBackupRepository;
use Convoy\Services\ProxmoxService;

class BackupCreationService extends ProxmoxService
{
    public function __construct(protected ProxmoxBackupRepository $repository)
    {
    }

    public function handle(string $mode, string $compressionType): ?string
    {
        $backups = $this->repository->setServer($this->server)->getBackups();

        if (! $this->server->backup_limit || count($backups) >= $this->server->backup_limit) {
            if ((int) $this->server->backup_limit <= 0 && isset($this->server->backup_limit)) {
                throw new TooManyBackupsException((int) $this->server->backup_limit);
            }
        }

        return $this->repository->backup($mode, $compressionType);
    }
}
