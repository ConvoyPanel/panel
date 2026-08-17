<?php

namespace App\Http\Controllers\Admin;

use App\Data\Storage\StorageEloquentData;
use App\Http\Controllers\Controller;
use App\Models\Storage;
use Spatie\LaravelData\DataCollection;

/**
 * Every storage Convoy knows about, across every node.
 *
 * A storage stopped belonging to a node once one pool could be attached to
 * several, and a pool that four hosts mount has no home on any single node's
 * tab. This is where "where in the fleet do I have room" and "who is on this
 * array" are answerable.
 *
 * Deliberately reads **recorded** capacity only. The node-scoped list can afford
 * one live Proxmox call because it is one node; a fleet-wide page would make one
 * per node, and an unreachable node among them would stall the whole thing for a
 * full connect timeout. The poll already writes these figures every minute.
 */
class StorageInventoryController extends Controller
{
    public function __invoke()
    {
        $storages = Storage::query()
            // Attached to at least one node, matching what the admin overview
            // already counts as fleet capacity: a storage no node reaches
            // cannot be deployed to, so listing it here would bury the ones
            // that can be.
            ->whereHas('nodes')
            ->withUsageSums()
            ->with('nodes')
            ->get();

        return StorageEloquentData::collect(
            $storages
                // No `$viewedFrom`, so `sharedWith` names every node the storage
                // reaches rather than "the others" -- which is what a list with
                // no node in scope should show.
                ->map(fn (Storage $storage) => StorageEloquentData::fromModel($storage))
                ->all(),
            DataCollection::class,
        );
    }
}
