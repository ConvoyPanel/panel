<?php

namespace App\Services\Nodes;

use App\Data\Cluster\StorageResourceData;
use App\Models\Node;
use App\Models\Storage;
use App\Models\StorageToNode;
use Illuminate\Support\Collection;

/**
 * Attaches a shared pool to every node in its cluster that Proxmox reports it on.
 *
 * Which nodes can reach a storage is not something an operator should have to
 * tell Convoy: `storage.cfg` is cluster-wide, its `nodes` property already says
 * where each storage is available, and `/cluster/resources` returns one
 * `type=storage` row per (storage, node) pair for the whole cluster. The poll
 * receives that map already and used to discard everything but the node it was
 * polling.
 *
 * **Only `shared` storages.** For a local backend the same name on three hosts is
 * three physically different disks -- `local-lvm` exists on every node under one
 * cluster-wide definition -- so linking those into one row would assert one pool
 * where there are three, which is the exact error the Nodes column exists to
 * expose. PVE's `shared` flag is the only thing that says "same content on all
 * nodes", so it is the only thing that licenses this.
 */
class SharedStorageLinkService
{
    /**
     * @param  Collection<int, StorageResourceData>  $storages  every row in the response,
     *                                                          not just the polled node's
     */
    public function handle(Node $polled, ?string $clusterName, Collection $storages): void
    {
        // A standalone host shares nothing by definition, and without a cluster
        // there is no scope in which two storage ids mean the same pool.
        if ($clusterName === null) {
            return;
        }

        $shared = $storages->filter(fn (StorageResourceData $storage) => $storage->shared);

        if ($shared->isEmpty()) {
            return;
        }

        /** @var Collection<string, Node> $peers */
        $peers = Node::query()
            ->where('cluster_name', $clusterName)
            ->get()
            ->keyBy('name');

        // Only pools Convoy has been told to manage. Registering a storage is
        // the operator's decision -- this answers "where is it", never "should
        // we use it".
        $registered = Storage::query()
            ->whereHas('nodes', fn ($query) => $query->where('nodes.cluster_name', $clusterName))
            ->with('nodes')
            ->get()
            ->keyBy('name');

        foreach ($shared as $row) {
            $node = $peers->get($row->nodeName);
            $storage = $registered->get($row->name);

            // A host Convoy does not manage, or a pool nobody registered.
            if ($node === null || $storage === null) {
                continue;
            }

            if ($storage->nodes->contains('id', $node->id)) {
                continue;
            }

            StorageToNode::create([
                'storage_id' => $storage->id,
                'node_id' => $node->id,
            ]);

            // Keep the in-memory copy honest so a storage reported twice in one
            // response is not inserted twice.
            $storage->nodes->push($node);
        }
    }
}
