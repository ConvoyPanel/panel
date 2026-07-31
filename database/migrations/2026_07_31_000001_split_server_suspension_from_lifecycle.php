<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Split the two facts that `servers.status` was carrying.
     *
     * Suspension is orthogonal to the lifecycle: a server is suspended *and* installed, not
     * suspended *instead of* installed. Storing both in one column meant suspending destroyed
     * whatever stage the server was in, and unsuspending had nothing to restore -- so the old
     * suspension service wrote NULL and hoped `ready` was the right answer. Since
     * `2025_07_22_183612_make_server_status_nonnullable` that write has been a not-null
     * violation outright.
     *
     * After this: `lifecycle` is a single axis (where the server is in provisioning) and
     * `suspended_at` is an independent flag. Neither is derived from the other.
     */
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->timestamp('suspended_at')->nullable()->after('status');
        });

        // The old value carried no suspension time, so `now` is the only honest answer -- these
        // rows were suspended at some unknown point before this migration ran. The lifecycle
        // stage they were at before suspension is not recoverable (the old column overwrote it),
        // and `ready` is the stage a suspendable server was overwhelmingly likely to be in.
        DB::table('servers')
            ->where('status', 'suspended')
            ->update([
                'suspended_at' => now(),
                'status' => 'ready',
            ]);

        // Postgres carries the NOT NULL and the `ready` default across a rename, so the column's
        // shape is unchanged -- only its name and the set of values it can hold.
        Schema::table('servers', function (Blueprint $table) {
            $table->renameColumn('status', 'lifecycle');
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->renameColumn('lifecycle', 'status');
        });

        // Folding suspension back in is lossy in the same way it always was: the lifecycle stage
        // of a suspended server is overwritten, because the old schema had nowhere to keep it.
        DB::table('servers')
            ->whereNotNull('suspended_at')
            ->update(['status' => 'suspended']);

        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('suspended_at');
        });
    }
};
