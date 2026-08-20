<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Observed capacity moves from the storage row to the (storage, node) link.
 *
 * Whether a datastore holds images or backups is decided cluster-wide in
 * `storage.cfg`, so `pve_type`/`pve_shared`/`pve_content` and the `stores_*`
 * flags stay on the definition. How full it is, is observed *per mount*: for a
 * shared pool every node reads the same figures, but for a local backend the
 * same definition names a physically different disk on every node. Keeping one
 * figure on the row is precisely what forced local pools into duplicate rows
 * -- with the figure on the link, one definition with N links carries N honest
 * readings and both cases are one shape.
 *
 * The copy gives every existing link its row's last observation: correct for
 * shared pools (one pool, one truth) and exact for v4's local rows (one link
 * each, so the reading was that node's own).
 *
 * The pair also gains its unique index here -- nothing enforced it before, so
 * exact duplicate links are collapsed first, keeping the row with the most
 * information.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storage_to_node', function (Blueprint $table) {
            $table->unsignedBigInteger('discovered_total')->nullable();
            $table->unsignedBigInteger('discovered_used')->nullable();
            $table->timestamp('discovered_at')->nullable();
        });

        $observed = DB::table('storages')
            ->whereNotNull('discovered_at')
            ->get(['id', 'discovered_total', 'discovered_used', 'discovered_at']);

        foreach ($observed as $storage) {
            DB::table('storage_to_node')
                ->where('storage_id', $storage->id)
                ->update([
                    'discovered_total' => $storage->discovered_total,
                    'discovered_used' => $storage->discovered_used,
                    'discovered_at' => $storage->discovered_at,
                ]);
        }

        // Collapse exact duplicate pairs (grouped in PHP: the table has no id
        // column to key a portable SQL dedupe on).
        $pairs = DB::table('storage_to_node')->get()->groupBy(
            fn ($link) => "{$link->storage_id}:{$link->node_id}",
        );

        foreach ($pairs as $rows) {
            if ($rows->count() < 2) {
                continue;
            }

            $keeper = clone $rows->sortByDesc(fn ($link) => $link->discovered_at ?? '')->first();

            DB::table('storage_to_node')
                ->where('storage_id', $keeper->storage_id)
                ->where('node_id', $keeper->node_id)
                ->delete();

            DB::table('storage_to_node')->insert((array) $keeper);
        }

        Schema::table('storage_to_node', function (Blueprint $table) {
            $table->unique(['storage_id', 'node_id']);
        });

        Schema::table('storages', function (Blueprint $table) {
            $table->dropColumn(['discovered_total', 'discovered_used', 'discovered_at']);
        });
    }

    public function down(): void
    {
        Schema::table('storages', function (Blueprint $table) {
            $table->unsignedBigInteger('discovered_total')->nullable()->after('pve_content');
            $table->unsignedBigInteger('discovered_used')->nullable()->after('discovered_total');
            $table->timestamp('discovered_at')->nullable()->after('discovered_used');
        });

        // Freshest link wins: for a shared pool any reading is the pool's, and
        // for a local one it is at least a real observation rather than none.
        $links = DB::table('storage_to_node')
            ->whereNotNull('discovered_at')
            ->orderBy('discovered_at')
            ->get();

        foreach ($links as $link) {
            DB::table('storages')->where('id', $link->storage_id)->update([
                'discovered_total' => $link->discovered_total,
                'discovered_used' => $link->discovered_used,
                'discovered_at' => $link->discovered_at,
            ]);
        }

        Schema::table('storage_to_node', function (Blueprint $table) {
            $table->dropUnique(['storage_id', 'node_id']);
            $table->dropColumn(['discovered_total', 'discovered_used', 'discovered_at']);
        });
    }
};
