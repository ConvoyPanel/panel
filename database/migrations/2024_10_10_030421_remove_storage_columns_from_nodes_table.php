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
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropColumn([
                'disk',
                'disk_overallocate',
                'vm_storage',
                'backup_storage',
                'iso_storage',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->after('memory_overallocate', function (Blueprint $table) {
                $table->integer('disk')->unsigned();
                $table->integer('disk_overallocate')->default(0);
                $table->string('vm_storage');
                $table->string('backup_storage');
                $table->string('iso_storage');
            });
        });
    }
};
