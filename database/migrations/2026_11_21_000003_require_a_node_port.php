<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * v4's 2023_05_21_015205 moved `nodes.port` with MySQL's MODIFY COLUMN, which
 * left it nullable, and 5.x kept that when it rewrote the migration for
 * Postgres. A fresh install has it required, so an upgraded one is brought
 * level. A node without a port gets Proxmox's.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('nodes')->whereNull('port')->update(['port' => 8006]);

        Schema::table('nodes', function (Blueprint $table) {
            $table->integer('port')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        // A fresh install had it required before this ran.
    }
};
