<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            DB::statement('ALTER TABLE nodes MODIFY COLUMN network varchar(255) AFTER backup_storage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            DB::statement('ALTER TABLE nodes MODIFY COLUMN network varchar(255) AFTER vm_storage');
        });
    }
};
