<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bandwidth-controls rework (issue #108, see docs/bandwidth-rate-limiting-plan.md):
 *
 * - servers.speed_limit         persistent per-server NIC speed cap, in BYTES/s
 *                               (decimal MB at the UI boundary). Null = unlimited.
 * - servers.overage_penalty     per-server override of the quota-overage penalty.
 * - nodes.overage_penalty       per-node override. Both null = inherit up the
 *                               cascade (server -> node -> BandwidthSettings global).
 * - servers.bandwidth_reset_day day-of-month (1-31) the monthly quota resets on,
 *                               seeded from created_at. Null falls back to
 *                               created_at at runtime.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->unsignedBigInteger('speed_limit')->nullable()->after('bandwidth_limit');
            $table->json('overage_penalty')->nullable()->after('speed_limit');
            $table->unsignedTinyInteger('bandwidth_reset_day')->nullable()->after('overage_penalty');
        });

        Schema::table('nodes', function (Blueprint $table) {
            $table->json('overage_penalty')->nullable()->after('memory_overallocate');
        });

        // Seed the reset anchor from each server's creation day (postgres).
        DB::statement('UPDATE servers SET bandwidth_reset_day = EXTRACT(DAY FROM created_at)');
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn(['speed_limit', 'overage_penalty', 'bandwidth_reset_day']);
        });

        Schema::table('nodes', function (Blueprint $table) {
            $table->dropColumn('overage_penalty');
        });
    }
};
