<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A `storages` row becomes what it is in PVE itself: one `storage.cfg`
 * definition per (cluster, name), instantiated on whichever nodes mount it.
 *
 * Until now the row's meaning was contested -- registration created one per
 * (node, name) while the shared-pool fan-out assumed one per cluster -- and
 * registering the same pool through three nodes produced three rows, of which
 * the fan-out silently kept the last. `unique (cluster_id, name)` settles the
 * question in the schema, where it cannot be re-litigated by service code.
 *
 * Backfill: each storage takes the scope of its first linked node. After the
 * node backfill every node sits in its own singleton scope, so this preserves
 * v4's per-node rows exactly; the first poll merges same-named rows when it
 * merges their nodes' scopes into a discovered cluster. Rows with no links --
 * already unreachable in v4 -- keep a null scope and stay out of everything,
 * including the unique constraint (nulls are distinct).
 *
 * Duplicates *within* one scope (the same name registered twice on one node,
 * which nothing used to prevent) are merged before the constraint lands:
 * every table referencing the loser is re-pointed at the keeper, links move
 * without creating duplicate pairs, and the loser is deleted.
 */
return new class extends Migration
{
    /** Tables whose `storage_id` must follow a merged row to its keeper. */
    private const REFERENCING_TABLES = ['servers', 'backups', 'iso_library', 'server_disks'];

    public function up(): void
    {
        Schema::table('storages', function (Blueprint $table) {
            $table->foreignId('cluster_id')
                ->nullable()
                ->after('id')
                ->constrained('clusters')
                ->nullOnDelete();
        });

        $links = DB::table('storage_to_node')
            ->join('nodes', 'nodes.id', '=', 'storage_to_node.node_id')
            ->orderBy('storage_to_node.node_id')
            ->get(['storage_to_node.storage_id', 'nodes.cluster_id']);

        foreach ($links->groupBy('storage_id') as $storageId => $rows) {
            DB::table('storages')
                ->where('id', $storageId)
                ->update(['cluster_id' => $rows->first()->cluster_id]);
        }

        $duplicates = DB::table('storages')
            ->select('cluster_id', 'name', DB::raw('min(id) as keeper_id'))
            ->whereNotNull('cluster_id')
            ->groupBy('cluster_id', 'name')
            ->havingRaw('count(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $loserIds = DB::table('storages')
                ->where('cluster_id', $duplicate->cluster_id)
                ->where('name', $duplicate->name)
                ->where('id', '!=', $duplicate->keeper_id)
                ->pluck('id');

            foreach (self::REFERENCING_TABLES as $table) {
                DB::table($table)
                    ->whereIn('storage_id', $loserIds)
                    ->update(['storage_id' => $duplicate->keeper_id]);
            }

            // Links move one at a time so a pair the keeper already has is
            // dropped rather than duplicated.
            foreach (DB::table('storage_to_node')->whereIn('storage_id', $loserIds)->get() as $link) {
                $keeperHasPair = DB::table('storage_to_node')
                    ->where('storage_id', $duplicate->keeper_id)
                    ->where('node_id', $link->node_id)
                    ->exists();

                $query = DB::table('storage_to_node')
                    ->where('storage_id', $link->storage_id)
                    ->where('node_id', $link->node_id);

                $keeperHasPair
                    ? $query->delete()
                    : $query->update(['storage_id' => $duplicate->keeper_id]);
            }

            DB::table('storages')->whereIn('id', $loserIds)->delete();
        }

        Schema::table('storages', function (Blueprint $table) {
            $table->unique(['cluster_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('storages', function (Blueprint $table) {
            $table->dropUnique(['cluster_id', 'name']);
            $table->dropConstrainedForeignId('cluster_id');
        });
    }
};
