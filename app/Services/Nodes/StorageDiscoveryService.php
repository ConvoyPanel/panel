<?php

namespace App\Services\Nodes;

use App\Data\Cluster\StorageResourceData;
use App\Enums\Node\Storage\StorageContentType;
use App\Models\Node;
use App\Models\Storage;
use Illuminate\Support\Collection;

/**
 * Records what Proxmox says about the storages Convoy has registered.
 *
 * Convoy's `storages` rows carry a name and a size taken from the host once, at
 * registration, and never checked again -- so either can drift. This writes
 * PVE's answer alongside the declaration so the two can be compared, and so the
 * UI can show the real figure while a node is up and fall back to the declared
 * one when it is not.
 *
 * Content types are not merely recorded but adopted: `stores_*` is overwritten
 * from PVE's list on every poll. What a storage may hold is decided in
 * `storage.cfg` and enforced by PVE, so a separate answer in Convoy could only
 * ever agree with it or be wrong -- and being wrong meant offering an operator a
 * storage that would reject the write.
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
                // Adopted, not just recorded -- see the note above. Skipped
                // when the row carried no content list at all: that is a report
                // that did not say, not a storage that holds nothing, and
                // deriving from it would silently strip every flag.
                ...$live->content !== null
                    ? StorageContentType::flagsFor($live->content)
                    : [],
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
