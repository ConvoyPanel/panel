<?php

namespace Convoy\Services\Servers\Backups;

use Convoy\Repositories\Proxmox\Server\ProxmoxBackupRepository;
use Convoy\Services\ProxmoxService;

class BackupDeletionService extends ProxmoxService
{
    public function __construct(protected ProxmoxBackupRepository $repository)
    {
    }

    public function handle(string $backupId): ?string
    {
        return $this->repository->setServer($this->server)->delete($backupId);
    }
}
