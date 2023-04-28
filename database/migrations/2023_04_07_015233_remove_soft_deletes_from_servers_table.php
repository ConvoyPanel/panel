<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            DB::table('servers')
                ->whereNotNull('deleted_at')
                ->delete();

            $table->dropSoftDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });
    }
};
