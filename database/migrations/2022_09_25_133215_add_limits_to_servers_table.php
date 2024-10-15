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
            $table->string('status')->after('description')->nullable();

            $table->after('installing', function ($table) {
                $table->integer('cpu')->unsigned();
                $table->integer('memory')->unsigned();
                $table->integer('disk')->unsigned();

                $table->integer('snapshot_limit')->unsigned()->nullable();
                $table->integer('backup_limit')->unsigned()->nullable();
                $table->integer('bandwidth_limit')->unsigned()->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn(['status', 'cpu', 'memory', 'disk', 'snapshot_limit', 'backup_limit', 'bandwidth_limit']);
        });
    }
};
