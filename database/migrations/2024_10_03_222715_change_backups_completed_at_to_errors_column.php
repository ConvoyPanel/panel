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
        // Delete records based on conditions
        DB::statement(
            'DELETE FROM backups WHERE completed_at IS NOT NULL AND is_successful = 0;',
        );

        Schema::table('backups', function (Blueprint $table) {
            $table->string('description')->nullable()->after('name');
            $table->string('errors')->nullable()->after('description');

            $table->dropSoftDeletes();
            $table->dropColumn(['is_successful', 'updated_at']);
        });

        // Modify the 'is_locked' column to be not nullable with a default of false (0)
        DB::statement(
            'ALTER TABLE backups MODIFY COLUMN is_locked TINYINT(1) NOT NULL DEFAULT 0 AFTER description',
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->dropColumn(['description', 'errors']);

            $table->boolean('is_locked')->nullable()->change();
            $table->boolean('is_successful')->default(false)->after('server_id');
            $table->timestamp('updated_at')->nullable()->after('created_at');
            $table->softDeletes()->after('updated_at');
        });

        // Modify the 'is_locked' column to be nullable in the down migration
        DB::statement(
            'ALTER TABLE backups MODIFY COLUMN is_locked TINYINT(1) NULL AFTER is_successful',
        );
    }
};