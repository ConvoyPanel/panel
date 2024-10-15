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
            $table->renameColumn('storage', 'backup_storage');
            $table->dropColumn(['last_pinged', 'latency']);
            $table->after('port', function (Blueprint $table) {
                $table->integer('memory')->unsigned();
                $table->integer('memory_overallocate')->default(0);
                $table->integer('disk')->unsigned();
                $table->integer('disk_overallocate')->default(0);
                $table->string('vm_storage');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->renameColumn('backup_storage', 'storage');
            $table->after('port', function (Blueprint $table) {
                $table->integer('latency')->nullable();
                $table->timestamp('last_pinged')->nullable();
            });
            $table->dropColumn(['memory', 'memory_overallocate', 'disk', 'disk_overallocate', 'vm_storage']);
        });
    }
};
