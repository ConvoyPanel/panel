<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Every node points at its storage scope; `cluster_name` retires.
 *
 * The backfill gives each existing node its own singleton scope rather than
 * grouping nodes that share a `cluster_name` -- the very ambiguity this schema
 * exists to remove is that a name is not an identity, so a data migration must
 * not mint identities from names. A singleton scope per node is exactly v4's
 * semantics (every storage row was node-scoped in practice), which makes the
 * upgrade behaviour-preserving: the first poll after upgrading discovers each
 * real cluster by CA fingerprint and merges its members' scopes then, with the
 * evidence in hand instead of guessed here.
 *
 * The old `cluster_name` is kept as the singleton's display label so the nodes
 * page reads the same before and after that first poll.
 *
 * Null `cluster_id` means "not yet discovered" -- a node registered while its
 * host was unreachable. Registration and the poll both resolve it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->foreignId('cluster_id')
                ->nullable()
                ->after('name')
                ->constrained('clusters')
                ->nullOnDelete();
        });

        $now = now();

        foreach (DB::table('nodes')->get(['id', 'cluster_name']) as $node) {
            $clusterId = DB::table('clusters')->insertGetId([
                'fingerprint' => null,
                'name' => $node->cluster_name,
                'member_names' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('nodes')->where('id', $node->id)->update(['cluster_id' => $clusterId]);
        }

        Schema::table('nodes', function (Blueprint $table) {
            $table->dropColumn('cluster_name');
        });
    }

    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->string('cluster_name')->nullable()->after('name');
        });

        // Real clusters get their label back; a singleton's label was only
        // ever a leftover of the forward migration, but restoring it loses
        // nothing either way.
        foreach (DB::table('clusters')->whereNotNull('name')->get(['id', 'name']) as $cluster) {
            DB::table('nodes')->where('cluster_id', $cluster->id)->update(['cluster_name' => $cluster->name]);
        }

        Schema::table('nodes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cluster_id');
        });
    }
};
