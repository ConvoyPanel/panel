<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The id a billing system or other integration knows a server or an account by.
 *
 * Unique, so an integration that retries a create after a timeout gets a validation error
 * naming the record it already made instead of a second VM. Nullable, because most rows were
 * made by hand and never had one; Postgres lets any number of rows share NULL under a unique
 * index.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->string('external_id', 191)->nullable()->unique()->after('uuid_short');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('external_id', 191)->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropUnique(['external_id']);
            $table->dropColumn('external_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['external_id']);
            $table->dropColumn('external_id');
        });
    }
};
