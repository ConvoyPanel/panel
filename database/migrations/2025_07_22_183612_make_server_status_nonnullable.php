<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Set all NULL statuses to 'ready' before making the column non-nullable
        DB::table('servers')->whereNull('status')->update(['status' => 'ready']);

        Schema::table('servers', function (Blueprint $table) {
            $table->string('status')->default('ready')->change();
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->string('status')->nullable()->change();
        });
    }
};
