<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Added nullable, filled, then made required. Postgres won't add a NOT NULL column
        // without a default to a table that has rows, so on an upgrade from 5.x the one-step
        // version failed on the first node.
        Schema::table('nodes', function (Blueprint $table) {
            $table->after('token_secret', function (Blueprint $table) {
                $table->integer('socket_count')->nullable();
                $table->integer('core_count')->nullable();
                $table->integer('cpu_count')->nullable();
            });
        });

        DB::table('nodes')->update([
            'socket_count' => 1,
            'core_count' => 1,
            'cpu_count' => 1,
        ]);

        Schema::table('nodes', function (Blueprint $table) {
            $table->integer('socket_count')->nullable(false)->change();
            $table->integer('core_count')->nullable(false)->change();
            $table->integer('cpu_count')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropColumn('socket_count');
            $table->dropColumn('core_count');
            $table->dropColumn('cpu_count');
        });
    }
};
