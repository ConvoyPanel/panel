<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storage_to_node', function (Blueprint $table) {
            $table->integer('backup_order')->nullable()->after('node_id');
        });
    }

    public function down(): void
    {
        Schema::table('storage_to_node', function (Blueprint $table) {
            $table->dropColumn('backup_order');
        });
    }
};
