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
        DB::statement(
            'ALTER TABLE servers MODIFY COLUMN snapshot_limit INT NULL;',
        );
        DB::statement(
            'ALTER TABLE servers MODIFY COLUMN backup_limit INT NULL;',
        );
        DB::statement(
            'ALTER TABLE servers MODIFY COLUMN bandwidth_limit INT NULL;',
        );
        DB::statement(
            'ALTER TABLE servers MODIFY COLUMN bandwidth_usage INT UNSIGNED NOT NULL DEFAULT 0;',
        );

        DB::statement('UPDATE servers SET snapshot_limit = -1 WHERE snapshot_limit IS NULL;');
        DB::statement('UPDATE servers SET backup_limit = -1 WHERE backup_limit IS NULL;');
        DB::statement('UPDATE servers SET bandwidth_limit = -1 WHERE bandwidth_limit IS NULL;');

        DB::statement(
            'ALTER TABLE servers CHANGE COLUMN snapshot_limit snapshot_count_limit INT NOT NULL;',
        );
        DB::statement(
            'ALTER TABLE servers CHANGE COLUMN backup_limit backup_count_limit INT NOT NULL;',
        );
        DB::statement(
            'ALTER TABLE servers CHANGE COLUMN bandwidth_limit bandwidth_limit INT NOT NULL;',
        );


        Schema::table('servers', function (Blueprint $table) {
            $table->integer('snapshot_size_limit')->after('snapshot_count_limit');
            $table->integer('backup_size_limit')->after('backup_count_limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement(
            'ALTER TABLE servers MODIFY COLUMN snapshot_count_limit INT NULL;',
        );
        DB::statement(
            'ALTER TABLE servers MODIFY COLUMN backup_count_limit INT NULL;',
        );
        DB::statement(
            'ALTER TABLE servers MODIFY COLUMN bandwidth_limit INT NULL;',
        );
        DB::statement(
            'ALTER TABLE servers MODIFY COLUMN bandwidth_usage INT NOT NULL DEFAULT 0;',
        );

        DB::statement(
            'UPDATE servers SET snapshot_count_limit = NULL WHERE snapshot_count_limit = -1;',
        );
        DB::statement(
            'UPDATE servers SET backup_count_limit = NULL WHERE backup_count_limit = -1;',
        );
        DB::statement('UPDATE servers SET bandwidth_limit = NULL WHERE bandwidth_limit = -1;');

        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('snapshot_size_limit', 'backup_size_limit');
        });

        DB::statement(
            'ALTER TABLE servers CHANGE COLUMN snapshot_count_limit snapshot_limit INT UNSIGNED NULL;',
        );
        DB::statement(
            'ALTER TABLE servers CHANGE COLUMN backup_count_limit backup_limit INT UNSIGNED NULL;',
        );
        DB::statement(
            'ALTER TABLE servers CHANGE COLUMN bandwidth_limit bandwidth_limit INT UNSIGNED NULL;',
        );
    }
};