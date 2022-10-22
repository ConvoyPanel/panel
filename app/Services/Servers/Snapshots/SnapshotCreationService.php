<?php

namespace Convoy\Services\Servers\Snapshots;

use Convoy\Exceptions\Service\Snapshot\TooManySnapshotsException;
use Convoy\Repositories\Proxmox\Server\ProxmoxSnapshotRepository;
use Convoy\Services\ProxmoxService;

class SnapshotCreationService extends ProxmoxService
{
    public function __construct(protected ProxmoxSnapshotRepository $repository)
    {
    }

    public function handle(string $name): ?string
    {
        $snapshots = $this->repository->setServer($this->server)->getSnapshots();

        if (! $this->server->snapshot_limit || (count($snapshots) - 1) >= $this->server->snapshot_limit) {
            if ((int) $this->server->snapshot_limit <= 0 && isset($this->server->snapshot_limit)) {
                throw new TooManySnapshotsException((int) $this->server->snapshot_limit);
            }
        }

        return $this->repository->create($name);
    }
}
