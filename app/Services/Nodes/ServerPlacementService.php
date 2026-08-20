<?php

namespace App\Services\Nodes;

use App\Data\Cluster\ServerResourceData;
use App\Enums\Audit\AuditEvent;
use App\Enums\Server\ProxmoxLock;
use App\Exceptions\Proxmox\RequestException as ConvoyRequestException;
use App\Models\Cluster;
use App\Models\NetworkInterface;
use App\Models\Node;
use App\Models\Server;
use App\Models\SystemActor;
use App\Services\Audit\AuditLogger;
use App\Services\Proxmox\Server\ProxmoxConfigClient;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Reconciles where Convoy says each server lives against where the cluster
 * says its guest is actually running.
 *
 * PVE moves guests without asking: HA recovery steals a dead node's config
 * files and cold-starts the guests elsewhere, and planned migrations do the
 * same cooperatively. The vmid never changes and `/cluster/resources` reports
 * the authoritative placement, so the poll that already fetches it hands the
 * unfiltered guest list here and this compares it against `servers.node_id` --
 * the column every Proxmox API URL is derived from, which is exactly why a
 * stale value quietly breaks power actions, stat sync, and firewall writes.
 *
 * Same temperament as {@see ClusterIdentityService}: act only on the
 * unambiguous case -- same cluster, vmid unique, identity confirmed, target
 * registered -- and flag the server for a human on anything else. Within one
 * cluster the (cluster, vmid) pair is already an identity PVE itself enforces;
 * the stamped SMBIOS UUID (see VmSyncService) is checked on top when the
 * server has one, and its absence is not suspicious -- servers built before
 * the stamp existed never get one retroactively.
 *
 * Re-homing also re-points `network_interface_id`, because bridges are
 * per-node rows and PVE's own model is that same-named bridges on different
 * nodes are the same network -- resolution by name is not an approximation
 * of PVE's behaviour, it *is* PVE's behaviour. A guest recovered onto a node
 * with no same-named bridge could not have been started by PVE either; that
 * case clears the link and flags rather than guessing.
 */
class ServerPlacementService
{
    public function __construct(
        private ProxmoxConfigClient $configClient,
        private AuditLogger $audit,
        private ConnectionInterface $connection,
    ) {}

    /**
     * @param  Collection<int, ServerResourceData>  $guests  every guest in the
     *   snapshot, unfiltered -- each row carries the node it is actually on
     */
    public function reconcile(?Cluster $cluster, Collection $guests): void
    {
        // Standalone scopes have nowhere for a guest to move to, an unresolved
        // node has no scope to compare within, and a flagged cluster's identity
        // is itself in doubt -- re-homing on top of that would compound a guess.
        if ($cluster === null || $cluster->isStandalone() || $cluster->flagged_at !== null) {
            return;
        }

        // Every member's poll sees the whole cluster, so N nodes would run
        // this N times a minute; the first poll of the cycle does the work.
        $lock = Cache::lock("cluster:{$cluster->id}:server-placement", 55);

        if (! $lock->get()) {
            return;
        }

        try {
            $this->reconcileScope($cluster, $guests);
        } finally {
            $lock->release();
        }
    }

    /**
     * @param  Collection<int, ServerResourceData>  $guests
     */
    private function reconcileScope(Cluster $cluster, Collection $guests): void
    {
        $reported = $guests
            ->filter(fn (ServerResourceData $guest) => ! $guest->isTemplate && $guest->nodeName !== null)
            ->keyBy(fn (ServerResourceData $guest) => $guest->vmid);

        $servers = Server::query()
            ->whereHas('node', fn (Builder $query) => $query->where('cluster_id', $cluster->id))
            ->with(['node', 'networkInterface'])
            ->get();

        $vmidCounts = $servers->countBy('vmid');

        foreach ($servers as $server) {
            $guest = $reported->get($server->vmid);

            // A guest missing from the snapshot entirely is the existing
            // "removed outside Convoy" story (see GuestStateCache), not a
            // placement question; a guest on the recorded node is in place.
            if ($guest === null || $guest->nodeName === $server->node->name) {
                continue;
            }

            if ($vmidCounts[$server->vmid] > 1) {
                $this->flag($server, sprintf(
                    'VMID %d is held by more than one server in this cluster; cannot tell which one moved to %s.',
                    $server->vmid,
                    $guest->nodeName,
                ));

                continue;
            }

            // Mid-migration the guest is transiently visible on the target;
            // the next poll sees where it actually ended up.
            if ($guest->lockStatus === ProxmoxLock::MIGRATE) {
                continue;
            }

            $target = Node::query()
                ->where('cluster_id', $cluster->id)
                ->where('name', $guest->nodeName)
                ->first();

            if ($target === null) {
                $this->flag($server, sprintf(
                    'Guest %d moved to cluster member "%s", which is not registered in Convoy.',
                    $server->vmid,
                    $guest->nodeName,
                ));

                continue;
            }

            $confirmed = $this->confirmIdentity($server, $target);

            // null: the target could not be asked right now. Not evidence of
            // anything -- leave the row alone and let a later poll decide.
            if ($confirmed === null) {
                continue;
            }

            if (! $confirmed) {
                $this->flag($server, sprintf(
                    'Guest %d on "%s" does not carry this server\'s SMBIOS UUID; refusing to re-home.',
                    $server->vmid,
                    $guest->nodeName,
                ));

                continue;
            }

            $this->rehome($server, $target);
        }
    }

    /**
     * Whether the guest on the target node is the same machine this row
     * describes. True when the stamped SMBIOS UUID matches, or when the server
     * predates stamping (the (cluster, vmid) pair is then the identity, which
     * PVE itself enforces unique). Null when the config cannot be fetched.
     */
    private function confirmIdentity(Server $server, Node $target): ?bool
    {
        if ($server->smbios_uuid === null) {
            return true;
        }

        try {
            $config = $this->configClient->setServer($server)->setNode($target)->getRawConfig();
        } catch (ConvoyRequestException|GuzzleRequestException|ConnectionException) {
            return null;
        }

        return $this->smbiosUuid($config['smbios1'] ?? null) === Str::lower($server->smbios_uuid);
    }

    private function smbiosUuid(?string $smbios): ?string
    {
        if ($smbios === null) {
            return null;
        }

        $uuid = Str::match('/(?:^|,)uuid=([0-9a-fA-F-]{36})/', $smbios);

        return $uuid === '' ? null : Str::lower($uuid);
    }

    /**
     * Point the row at where the guest actually is. The interface link moves
     * with it by bridge name -- or is cleared and the server flagged when the
     * target has no such bridge, because a network sync through a bridge the
     * node doesn't have is how a survived failover turns into an outage.
     */
    private function rehome(Server $server, Node $target): void
    {
        $previous = $server->node;
        $bridge = $server->networkInterface?->name;

        $interface = $bridge === null ? null : NetworkInterface::query()
            ->where('node_id', $target->id)
            ->where('name', $bridge)
            ->first();

        $this->connection->transaction(function () use ($server, $target, $previous, $bridge, $interface) {
            $server->forceFill([
                'node_id' => $target->id,
                'network_interface_id' => $interface?->id,
                // A clean re-home resolves whatever placement anomaly was
                // flagged before (e.g. the target node has since been
                // registered); a missing bridge immediately re-flags below.
                'flagged_at' => null,
                'flag_reason' => null,
            ])->save();

            $this->audit->record(
                AuditEvent::ADMIN_SERVER_REHOMED,
                $server,
                ['from' => $previous->name, 'to' => $target->name, 'vmid' => $server->vmid],
                SystemActor::instance(),
            );
        });

        if ($bridge !== null && $interface === null) {
            $this->flag($server, sprintf(
                'Re-homed to "%s", but it has no bridge named "%s"; the interface link was cleared and network sync is blocked until an operator resolves it.',
                $target->name,
                $bridge,
            ));
        }
    }

    /**
     * Record why this row was left alone. First observation wins -- the same
     * anomaly is re-observed every poll, and overwriting the timestamp each
     * minute would hide how long it has been standing.
     */
    private function flag(Server $server, string $reason): void
    {
        if ($server->flagged_at !== null) {
            return;
        }

        $server->forceFill(['flagged_at' => now(), 'flag_reason' => $reason])->save();
    }
}
