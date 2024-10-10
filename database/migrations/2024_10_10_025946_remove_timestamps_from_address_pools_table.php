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
        Schema::table('address_pools', function (Blueprint $table) {
            $table->dropColumn('created_at', 'updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('address_pools', function (Blueprint $table) {
            $table->after('name', fn (Blueprint $table) => $table->timestamps());
        });

        DB::statement('UPDATE address_pools SET created_at = NOW()');
    }
};
