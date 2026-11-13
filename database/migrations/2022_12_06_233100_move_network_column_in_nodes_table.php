<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This used to be `ALTER TABLE nodes MODIFY COLUMN network varchar(255) AFTER
     * backup_storage`, MySQL-only SQL that only moved the column. MODIFY COLUMN also
     * redefines it, and since that statement didn't say NOT NULL, MySQL made the
     * column nullable on every install that ran it. Postgres can't reorder columns, so
     * what's kept is the part that changed the schema.
     */
    public function up(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->string('network')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations. The original down() moved the column back and left it
     * nullable as well, so there is nothing to undo.
     */
    public function down(): void
    {
        //
    }
};
