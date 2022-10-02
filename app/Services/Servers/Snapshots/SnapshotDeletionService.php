<?php

namespace Convoy\Services\Servers\Snapshots;

use Convoy\Repositories\Proxmox\Server\ProxmoxSnapshotRepository;
use Convoy\Services\ProxmoxService;

class SnapshotDeletionService extends ProxmoxService
{
    public function __construct(protected ProxmoxSnapshotRepository $repository)
    {
    }
    public function handle(string $name): ?string
    {
        return $this->repository->setServer($this->server)->delete($name);
    }
}
