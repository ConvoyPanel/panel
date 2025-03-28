<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->renameColumn('name', 'display_name');
            $table->renameColumn('cluster', 'name');
            $table->string('name')->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->renameColumn('name', 'cluster');
            $table->renameColumn('display_name', 'name');
            $table->string('cluster')->default('proxmox')->change();
        });
    }
};
