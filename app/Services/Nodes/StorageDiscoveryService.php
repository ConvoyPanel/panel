<?php

namespace App\Services\Nodes;

use App\Data\Cluster\StorageResourceData;
use App\Models\Node;
use App\Models\Storage;
use Illuminate\Support\Collection;

/**
 * Records what Proxmox says about the storages Convoy has registered.
 *
 * Convoy's `storages` rows are operator-declared: a name typed by hand, a size
 * typed by hand, content flags ticked by hand. None of it is checked against the
 * host, so any of it can drift. This writes PVE's answer alongside the
 * declaration so the two can be compared -- and so the UI can show the real
 * figure while a node is up and fall back to the declared one when it is not.
 *
 * The facts come from the `type=storage` rows of the poll's existing
 * `/cluster/resources` call, so discovery adds no request and no timeout.
 *
 * Matching is by name within the node's own storages. That is exactly as narrow
 * as Convoy's model currently allows: `StorageController::store()` attaches a new
 * storage to the one node it was created under, so a PVE storage visible on three
 * nodes is three rows here, and each matches against its own node's report.
 * Cluster-wide identity is a later phase.
 */
class StorageDiscoveryService
{
    /**
     * @param  Collection<int, StorageResourceData>  $storages  this node's rows, already filtered
     */
    public function handle(Node $node, Collection $storages): void
    {
        if ($storages->isEmpty()) {
            return;
        }

        $reported = $storages->keyBy(fn (StorageResourceData $storage) => $storage->name);

        // Only the storages Convoy knows about. A PVE storage nobody registered
        // is not an error and not ours to create -- deciding which stores Convoy
        // may use is the operator's call, and importing silently would make it
        // Convoy's.
        $node->storages()->get()->each(function (Storage $storage) use ($reported) {
            $live = $reported->get($storage->name);

            // Registered here but absent from PVE's report: leave the last known
            // values alone rather than blanking them. One poll that did not
            // mention a store is not evidence the store is gone, and wiping the
            // figures would turn a rename into "capacity unknown" everywhere.
            if ($live === null) {
                return;
            }

            $storage->forceFill([
                'pve_type' => $live->type,
                'pve_shared' => $live->shared,
                'pve_content' => $live->content,
                // `available` is PVE saying it actually read the store. Anything
                // else means the numbers beside it are not worth recording, so
                // the previous ones stand.
                ...$live->status === 'available' ? [
                    'discovered_total' => $live->total,
                    'discovered_used' => $live->used,
                    'discovered_at' => now(),
                ] : [],
            ])->save();
        });
    }
}
