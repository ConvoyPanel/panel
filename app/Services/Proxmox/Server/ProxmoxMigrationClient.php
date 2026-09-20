<?php

namespace App\Services\Proxmox\Server;

use App\Data\Server\Proxmox\Migration\MigrationPreconditionData;
use App\Exceptions\Proxmox\RequestException;
use App\Services\Proxmox\ProxmoxClient;
use Illuminate\Http\Client\ConnectionException;

/**
 * PVE's own opinion about whether a guest can move, and the call that moves it.
 *
 * Intra-cluster only: `POST .../migrate` has no bridge-remapping parameter and
 * assumes an identically named bridge on the destination. Cross-cluster is
 * `remote_migrate`, a different endpoint with a different contract, and is not
 * wired up.
 */
class ProxmoxMigrationClient extends ProxmoxClient
{
    /**
     * Ask the source node which members it would accept as a destination and
     * what is in the way. Cheap, read-only, and re-derives nothing Convoy would
     * have to guess at: storage availability, local disks, passed-through
     * devices and HA affinity all come back from here.
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getPreconditions(?string $target = null): MigrationPreconditionData
    {
        $response = $this->getHttpClientWithParams()
            ->get(
                '/api2/json/nodes/{node}/qemu/{server}/migrate',
                $target === null ? [] : ['target' => $target],
            )
            ->json();

        return MigrationPreconditionData::fromRaw($this->getData($response));
    }

    /**
     * Start the migration and return the UPID of the task it spawned.
     *
     * `online` is ignored by PVE when the guest is stopped, so the caller can
     * pass what it wants and let PVE decide. `with-local-disks` is always on:
     * without it a guest with any disk on node-local storage simply fails, and
     * a shared-storage guest has no local disks for it to affect.
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function migrate(string $target, bool $online): ?string
    {
        $response = $this->getHttpClientWithParams()
            ->post('/api2/json/nodes/{node}/qemu/{server}/migrate', [
                'target' => $target,
                'online' => $online,
                'with-local-disks' => true,
            ])
            ->json();

        $upid = $this->getData($response);

        return is_string($upid) ? $upid : null;
    }
}
