<?php

namespace App\Services\Proxmox\Server;

use App\Data\Server\Proxmox\Migration\MigrationPreconditionData;
use App\Exceptions\Proxmox\RequestException;
use App\Models\Node;
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
     * One guest's config, addressed by node and VMID rather than by a Server.
     *
     * The rest of the Proxmox clients build their URL from `$server->node` and
     * `$server->vmid`, which is right for every call made against the guest
     * the panel already knows about. The Anchor transport needs neither: the
     * guest it is asking about is on the *destination* node under a VMID the
     * destination chose, and the `servers` row still describes the source
     * until the rebind. Passing both explicitly is the only honest way to ask.
     *
     * @return array<string, mixed>
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getGuestConfig(Node $node, int $vmid): array
    {
        $response = $this->setNode($node)
            ->getHttpClientWithParams(['vmid' => $vmid])
            ->get('/api2/json/nodes/{node}/qemu/{vmid}/config')
            ->json();

        $config = $this->getData($response);

        return is_array($config) ? $config : [];
    }

    /**
     * Destroy one guest, addressed by node and VMID.
     *
     * Only ever pointed at a destination guest a failed migration left behind:
     * the source guest is destroyed through the normal delete path, against
     * the row that still describes it. `purge` clears the guest out of any job
     * and replication config so a half-restored VMID is genuinely free for the
     * retry; `destroy-unreferenced-disks` is off because a restore writes only
     * volumes it owns and nothing else on that VMID should be assumed ours.
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function destroyGuest(Node $node, int $vmid): ?string
    {
        // `purge` goes in the query string, not a body. Proxmox rejects a DELETE
        // that carries one with "Unexpected content for method 'DELETE'", and
        // the rollback swallows that as a warning -- so the orphaned guest was
        // silently left behind and a failed migration stranded the same server
        // on two nodes. Every other DELETE in this namespace passes no body at
        // all; this was the one that did.
        $response = $this->setNode($node)
            ->getHttpClientWithParams(['vmid' => $vmid])
            ->delete('/api2/json/nodes/{node}/qemu/{vmid}?purge=1')
            ->json();

        $upid = $this->getData($response);

        return is_string($upid) ? $upid : null;
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
