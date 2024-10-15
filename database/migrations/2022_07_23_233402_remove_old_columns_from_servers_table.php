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
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('vnc_username');
            $table->dropColumn('vnc_password');
            $table->dropColumn('cloud_init_enabled');
            $table->dropColumn('is_os_template');
            $table->boolean('is_template')->after('description')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->string('vnc_username')->after('description')->nullable();
            $table->string('vnc_password')->after('vnc_username')->nullable();
            $table->boolean('cloud_init_enabled')->after('vnc_password')->default(true);
            $table->boolean('is_os_template')->after('cloud_init_enabled')->default(false);
            $table->dropColumn('is_template');
        });
    }
};
