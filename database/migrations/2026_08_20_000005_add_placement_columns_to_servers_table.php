<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Placement reconciliation columns (#161).
 *
 * `smbios_uuid` is the identity Convoy stamps into the guest's `smbios1`
 * config at build time. The config file is exactly the thing PVE moves
 * between node directories on migration and HA recovery, so the value
 * travels with the VM and lets the reconciler confirm "same guest, new
 * node" before rewriting `node_id`. Null means the server predates the
 * stamp and identity rests on (cluster, vmid) alone -- which PVE itself
 * guarantees unique within a cluster -- so there is deliberately no
 * backfill: rewriting the SMBIOS UUID of a guest that has already booted
 * can trip Windows activation and licensing keyed to it.
 *
 * `flagged_at`/`flag_reason` mirror the columns on `clusters`: when the
 * reconciler sees a placement it cannot resolve safely (guest on a node
 * Convoy does not manage, no matching bridge to remap onto, ambiguous
 * vmid) it records why and leaves the row for a human instead of guessing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->char('smbios_uuid', 36)->nullable()->after('uuid_short');
            $table->timestamp('flagged_at')->nullable()->after('suspended_at');
            $table->string('flag_reason')->nullable()->after('flagged_at');
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn(['smbios_uuid', 'flagged_at', 'flag_reason']);
        });
    }
};
