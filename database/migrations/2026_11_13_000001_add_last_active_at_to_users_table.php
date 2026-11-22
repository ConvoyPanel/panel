<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The same migration, under the same name, as v4.7.0's and 5.x's, so a database
// upgraded from 5.x has already run it.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // The last signed-in request from a browser session. Session records can't answer
            // "who was active this month": they are deleted at logout and when they expire.
            $table->timestamp('last_active_at')->nullable()->index()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['last_active_at']);
            $table->dropColumn('last_active_at');
        });
    }
};
