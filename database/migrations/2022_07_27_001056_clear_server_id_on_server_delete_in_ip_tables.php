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
        Schema::table('ip_addresses', function (Blueprint $table) {
            $table->dropForeign(['server_id']);
            //$table->foreignId('server_id')->after('id')->nullable()->change()->constrained()->nullOnDelete();
            $table->foreign('server_id')
                ->references('id')
                ->on('servers')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ip_addresses', function (Blueprint $table) {
        });
    }
};
