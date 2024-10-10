<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE servers MODIFY COLUMN vmid INT UNSIGNED NOT NULL;',
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement(
            'ALTER TABLE servers MODIFY COLUMN vmid BIGINT UNSIGNED NOT NULL;',
        );
    }
};
