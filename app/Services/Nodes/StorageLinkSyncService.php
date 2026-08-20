<?php

namespace App\Services\Nodes;

use App\Data\Cluster\ClusterResourceSnapshot;
use App\Data\Cluster\NodeResourceData;
use App\Data\Cluster\StorageResourceData;
use App\Models\Cluster;
use App\Models\Node;
use App\Models\Storage;
use App\Models\StorageToNode;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Keeps the (storage, node) links of one scope matched to what Proxmox
 * reports: a sync, not an append.
 *
 * Which nodes can reach a storage is not something an operator should have to
 * tell Convoy: `storage.cfg` is cluster-wide and `/cluster/resources` returns
 * one `type=storage` row per (storage, node) pair for the whole cluster, so
 * one poll of any member describes everyone's mounts.
 *
 * **Attaching is for `shared` pools only.** For a local backend the same name
 * on three hosts is three physically different disks under one cluster-wide
 * definition, so fanning the links out would assert reachability that does
 * not exist; a local definition gains links only when an operator registers
 * it on a node. PVE's `shared` flag is the only thing that says "same content
 * on all nodes", so it is the only thing that licenses the fan-out.
 *
 * **Pruning applies to both.** A pair the response no longer reports -- for a
 * member the response itself lists as online, so its absence is a statement
 * rather than a blind spot -- means the storage is no longer configured
 * there, and the link is severed. Only links discovery has confirmed at least
 * once are eligible: an operator's fresh registration that PVE has not
 * acknowledged yet is their claim to make, and it stays visible so they can
 * see and fix it rather than watching it vanish.
 */
class StorageLinkSyncService
{
    public function handle(Node $polled, ?Cluster $cluster, ClusterResourceSnapshot $resources): void
    {
        // Unresolved scope: nothing can be safely said about reachability.
        if ($cluster === null) {
            return;
        }

        /** @var Collection<string, Node> $peers */
        $peers = $cluster->nodes()->get()->keyBy('name');

        // Only pools Convoy has been told to manage. Registering a storage is
        // the operator's decision -- this answers "where is it", never "should
        // we use it".
        $registered = Storage::query()
            ->where('cluster_id', $cluster->id)
            ->with('nodes')
            ->get()
            ->keyBy('name');

        if ($registered->isEmpty()) {
            return;
        }

        $this->attachShared($resources, $peers, $registered);
        $this->pruneVanished($resources, $peers, $registered);
    }

    /**
     * @param  Collection<string, Node>  $peers
     * @param  Collection<string, Storage>  $registered
     */
    private function attachShared(ClusterResourceSnapshot $resources, Collection $peers, Collection $registered): void
    {
        foreach ($resources->storages as $row) {
            if (! $row->shared) {
                continue;
            }

            $node = $peers->get($row->nodeName);
            $storage = $registered->get($row->name);

            // A host Convoy does not manage, or a pool nobody registered.
            if ($node === null || $storage === null) {
                continue;
            }

            if ($storage->nodes->contains('id', $node->id)) {
                continue;
            }

            try {
                StorageToNode::create([
                    'storage_id' => $storage->id,
                    'node_id' => $node->id,
                ]);
            } catch (UniqueConstraintViolationException) {
                // Two members polled in the same instant drew the same link;
                // the row exists, which is all this wanted.
            }

            // Keep the in-memory copy honest so a storage reported twice in one
            // response is not inserted twice.
            $storage->nodes->push($node);
        }
    }

    /**
     * @param  Collection<string, Node>  $peers
     * @param  Collection<string, Storage>  $registered
     */
    private function pruneVanished(ClusterResourceSnapshot $resources, Collection $peers, Collection $registered): void
    {
        // The response vouches only for members it lists as online: a member
        // that is down is absent, not detached, and severing its links would
        // turn an outage into a configuration change.
        $vouchedFor = $resources->nodes
            ->filter(fn (NodeResourceData $node) => $node->status === 'online')
            ->pluck('nodeName')
            ->flip();

        $reportedPairs = $resources->storages
            ->mapWithKeys(fn (StorageResourceData $row) => ["{$row->nodeName}|{$row->name}" => true]);

        foreach ($registered as $storage) {
            foreach ($storage->nodes as $node) {
                // Same name in another scope (possible mid-upgrade, while
                // members are still converging one by one) is not this node.
                $peer = $peers->get($node->name);

                if (
                    $peer === null
                    || $peer->id !== $node->id
                    || ! isset($vouchedFor[$node->name])
                    || isset($reportedPairs["{$node->name}|{$storage->name}"])
                    || $node->pivot->discovered_at === null
                ) {
                    continue;
                }

                StorageToNode::query()
                    ->where('storage_id', $storage->id)
                    ->where('node_id', $node->id)
                    ->delete();
            }
        }
    }
}
