<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->integer('snapshot_limit')->nullable()->change();
            $table->integer('backup_limit')->nullable()->change();
            $table->integer('bandwidth_limit')->nullable()->change();
            $table->unsignedInteger('bandwidth_usage')->default(0)->change();
        });

        DB::table('servers')->whereNull('snapshot_limit')->update(['snapshot_limit' => -1]);
        DB::table('servers')->whereNull('backup_limit')->update(['backup_limit' => -1]);
        DB::table('servers')->whereNull('bandwidth_limit')->update(['bandwidth_limit' => -1]);

        Schema::table('servers', function (Blueprint $table) {
            $table->renameColumn('snapshot_limit', 'snapshot_count_limit');
            $table->renameColumn('backup_limit', 'backup_count_limit');
            $table->integer('snapshot_size_limit')->after('snapshot_count_limit');
            $table->integer('backup_size_limit')->after('backup_count_limit');
        });

        Schema::table('servers', function (Blueprint $table) {
            $table->integer('snapshot_count_limit')->nullable(false)->change();
            $table->integer('backup_count_limit')->nullable(false)->change();
            $table->integer('bandwidth_limit')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->integer('snapshot_count_limit')->nullable()->change();
            $table->integer('backup_count_limit')->nullable()->change();
            $table->integer('bandwidth_limit')->nullable()->change();
            $table->integer('bandwidth_usage')->default(0)->change();
        });

        DB::table('servers')->where('snapshot_count_limit', -1)->update(['snapshot_count_limit' => null]);
        DB::table('servers')->where('backup_count_limit', -1)->update(['backup_count_limit' => null]);
        DB::table('servers')->where('bandwidth_limit', -1)->update(['bandwidth_limit' => null]);

        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('snapshot_size_limit', 'backup_size_limit');
            $table->renameColumn('snapshot_count_limit', 'snapshot_limit');
            $table->renameColumn('backup_count_limit', 'backup_limit');
        });
    }
};
