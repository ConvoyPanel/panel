<?php

namespace App\Services\Nodes;

use App\Exceptions\Proxmox\RequestException as ConvoyRequestException;
use App\Models\Backup;
use App\Models\Cluster;
use App\Models\ISO;
use App\Models\Node;
use App\Models\Server;
use App\Models\ServerDisk;
use App\Models\Storage;
use App\Models\StorageToNode;
use App\Services\Proxmox\Cluster\ProxmoxClusterStatusClient;
use App\Services\Proxmox\Node\ProxmoxCertificateClient;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\Client\ConnectionException;

/**
 * Resolves which storage scope a node is in, and moves it when the host says
 * it changed.
 *
 * Two signals, asked in order, each covering the other's blind spot:
 *
 * 1. **Is the node in a cluster at all?** The presence of a `type=cluster` row
 *    in `/cluster/status` -- live corosync state. Without it the node is
 *    standalone and gets a singleton scope keyed to itself, *never* to its
 *    certificate: a node separated from a cluster without a reinstall still
 *    carries the old cluster's CA, and keying by it would glue the node back
 *    onto the cluster it left.
 * 2. **Which cluster?** The cluster CA's fingerprint. The name is a label two
 *    unrelated clusters routinely share; the CA cannot be shared and does not
 *    change when members come and go, so it is fetched only when something
 *    suggests the answer moved -- steady-state polls cost no extra request.
 *
 * Re-homing fails toward under-offering. A node that joins a scope brings its
 * singleton storages with it (that is also how v4's per-node rows converge
 * into one definition after the upgrade); a node that *leaves* a cluster
 * takes nothing and keeps no links -- the pools belong to the cluster, and a
 * node that cannot prove it reaches them must not be offered them.
 *
 * The one identity even a certificate can fake -- a dirty-separated node
 * re-clustered without regenerating certs shares its old cluster's CA -- is
 * caught by the member tripwire: a reported member set with no overlap at all
 * with the stored one flags the cluster row for a human instead of silently
 * rewriting it.
 */
class ClusterIdentityService
{
    public function __construct(
        private ProxmoxClusterStatusClient $clusterStatus,
        private ProxmoxCertificateClient $certificates,
        private ConnectionInterface $connection,
    ) {}

    /**
     * The node's scope as the host reports it right now. Falls back to the
     * previously known scope when the host cannot be asked -- the caller has
     * already proven the node up, so a failure here is a failure of this
     * lookup, not of the node.
     */
    public function resolve(Node $node): ?Cluster
    {
        try {
            $status = $this->clusterStatus->setNode($node)->getStatus();
        } catch (ConvoyRequestException|GuzzleRequestException|ConnectionException) {
            return $node->cluster;
        }

        return $status->clusterName === null
            ? $this->resolveStandalone($node)
            : $this->resolveClustered($node, $status->clusterName, $status->memberNames);
    }

    private function resolveStandalone(Node $node): Cluster
    {
        $current = $node->cluster;

        if ($current !== null && $current->isStandalone()) {
            // The label lingers when the singleton was minted by the upgrade
            // migration from an old `cluster_name`; the host has now said
            // plainly that it is standalone.
            if ($current->name !== null || $current->member_names !== [$node->name]) {
                $current->forceFill(['name' => null, 'member_names' => [$node->name]])->save();
            }

            return $current;
        }

        return $this->rehome($node, Cluster::create([
            'fingerprint' => null,
            'name' => null,
            'member_names' => [$node->name],
        ]));
    }

    /**
     * @param  array<int, string>  $memberNames
     */
    private function resolveClustered(Node $node, string $clusterName, array $memberNames): ?Cluster
    {
        $current = $node->cluster;

        // Steady state: same cluster row, same label, members still overlap.
        // No certificate fetch -- identity questions only get asked when
        // something suggests the answer moved.
        if (
            $current !== null
            && ! $current->isStandalone()
            && $current->name === $clusterName
            && ! $this->isDisjoint($current->member_names, $memberNames)
        ) {
            $this->adoptReport($current, $clusterName, $memberNames);

            return $current;
        }

        try {
            $fingerprint = $this->certificates->setNode($node)->getClusterCaFingerprint();
        } catch (ConvoyRequestException|GuzzleRequestException|ConnectionException) {
            $fingerprint = null;
        }

        // Could not identify the cluster (lookup failed, or no CA row). The
        // previous answer stands; a node never resolved stays unresolved until
        // a poll can identify it.
        if ($fingerprint === null) {
            return $current;
        }

        $cluster = Cluster::query()->firstOrCreate(
            ['fingerprint' => $fingerprint],
            ['name' => $clusterName, 'member_names' => $memberNames],
        );

        if (! $cluster->wasRecentlyCreated) {
            $this->adoptReport($cluster, $clusterName, $memberNames);
        }

        return $node->cluster_id === $cluster->id
            ? $cluster
            : $this->rehome($node, $cluster);
    }

    /**
     * Record what the poll reported on the cluster row -- unless the member
     * set is disjoint from the stored one, which is the tripwire: either every
     * member was renamed at once, or two clusters share a CA (a separated node
     * re-clustered on the old certificate). Both deserve a human, so the row
     * is flagged and the stored members stand as evidence.
     */
    private function adoptReport(Cluster $cluster, string $clusterName, array $memberNames): void
    {
        if ($this->isDisjoint($cluster->member_names, $memberNames)) {
            if ($cluster->flagged_at === null) {
                $cluster->forceFill([
                    'flagged_at' => now(),
                    'flag_reason' => sprintf(
                        'Reported members [%s] share nothing with recorded members [%s].',
                        implode(', ', $memberNames),
                        implode(', ', $cluster->member_names ?? []),
                    ),
                ])->save();
            }

            return;
        }

        if ($cluster->name !== $clusterName || $cluster->member_names !== $memberNames) {
            $cluster->forceFill(['name' => $clusterName, 'member_names' => $memberNames])->save();
        }
    }

    /**
     * @param  ?array<int, string>  $stored
     * @param  array<int, string>  $reported
     */
    private function isDisjoint(?array $stored, array $reported): bool
    {
        if ($stored === null || $stored === [] || $reported === []) {
            return false;
        }

        return array_intersect($stored, $reported) === [];
    }

    /**
     * Move the node into its newly resolved scope.
     *
     * A singleton's storages come along and merge by name -- the node was the
     * scope, so its registrations are its own to carry (this is what folds
     * v4's per-node rows into one definition per cluster on the first poll
     * after upgrading). Leaving a *cluster* carries nothing: those pools
     * belong to the cluster, so the node's links to them are severed and its
     * new scope starts empty. An emptied singleton row is deleted.
     */
    private function rehome(Node $node, Cluster $cluster): Cluster
    {
        $previous = $node->cluster;

        $this->connection->transaction(function () use ($node, $cluster, $previous) {
            $node->forceFill(['cluster_id' => $cluster->id])->save();

            if ($previous === null) {
                return;
            }

            if ($previous->isStandalone()) {
                $previous->storages()
                    ->get()
                    ->each(fn (Storage $storage) => $this->mergeIntoScope($storage, $cluster));
            } else {
                StorageToNode::query()
                    ->where('node_id', $node->id)
                    ->whereIn('storage_id', $previous->storages()->select('id'))
                    ->delete();
            }

            if (
                $previous->isStandalone()
                && ! $previous->nodes()->exists()
                && ! $previous->storages()->exists()
            ) {
                $previous->delete();
            }
        });

        return $cluster;
    }

    /**
     * Move one storage definition into a scope, folding it into the scope's
     * same-named definition when one exists: everything referencing the
     * arriving row is re-pointed at the established one, links move without
     * ever duplicating a (storage, node) pair, and the arriving row is
     * deleted.
     */
    private function mergeIntoScope(Storage $arriving, Cluster $cluster): void
    {
        /** @var ?Storage $established */
        $established = $cluster->storages()->where('name', $arriving->name)->first();

        if ($established === null) {
            $arriving->forceFill(['cluster_id' => $cluster->id])->save();

            return;
        }

        foreach ([Server::class, Backup::class, ISO::class, ServerDisk::class] as $model) {
            $model::query()
                ->where('storage_id', $arriving->id)
                ->update(['storage_id' => $established->id]);
        }

        $establishedNodeIds = StorageToNode::query()
            ->where('storage_id', $established->id)
            ->pluck('node_id');

        StorageToNode::query()
            ->where('storage_id', $arriving->id)
            ->whereIn('node_id', $establishedNodeIds)
            ->delete();

        StorageToNode::query()
            ->where('storage_id', $arriving->id)
            ->update(['storage_id' => $established->id]);

        $arriving->delete();
    }
}
