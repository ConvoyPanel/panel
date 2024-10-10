<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE backups MODIFY COLUMN size INT UNSIGNED NOT NULL;',
        );
        DB::statement(
            'ALTER TABLE snapshots MODIFY COLUMN size INT UNSIGNED NOT NULL;',
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement(
            'ALTER TABLE backups MODIFY COLUMN size BIGINT UNSIGNED NOT NULL DEFAULT 0;',
        );
        DB::statement(
            'ALTER TABLE snapshots MODIFY COLUMN size BIGINT UNSIGNED NOT NULL DEFAULT 0;',
        );
    }
};
