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
            $table->dropColumn('installing');
            $table->integer('bandwidth_usage')->after('disk')->unsigned();
            $table->timestamp('hydrated_at')->after('bandwidth_limit')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->boolean('installing')->after('status')->default(false);
            $table->dropColumn(['hydrated_at', 'bandwidth_usage']);
        });
    }
};
