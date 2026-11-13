<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This used to be `ALTER TABLE template_groups MODIFY COLUMN node_id bigint
     * unsigned AFTER id`, MySQL-only SQL that only moved the column. It also left
     * node_id nullable, but the next migration
     * (2022_12_25_011355_make_node_id_not_nullable_in_template_groups_table) makes it
     * NOT NULL again, so nothing it did survives. Postgres can't reorder columns.
     */
    public function up(): void
    {
        //
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
