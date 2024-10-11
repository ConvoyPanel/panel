<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->foreignId('storage_id')->after('server_id')->constrained()->onDelete('cascade');
        });

        Schema::table('snapshots', function (Blueprint $table) {
            $table->foreignId('storage_id')->after('server_id')->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->dropForeign(['storage_id']);
            $table->dropColumn('storage_id');
        });

        Schema::table('snapshots', function (Blueprint $table) {
            $table->dropForeign(['storage_id']);
            $table->dropColumn('storage_id');
        });
    }
};
