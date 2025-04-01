<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->after('token_secret', function (Blueprint $table) {
                $table->integer('socket_count');
                $table->integer('core_count');
                $table->integer('cpu_count');
            });
        });

        DB::table('nodes')->update([
            'socket_count' => 1,
            'core_count' => 1,
            'cpu_count' => 1,
        ]);
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
