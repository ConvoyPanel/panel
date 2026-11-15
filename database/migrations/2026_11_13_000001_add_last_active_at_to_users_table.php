<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// v4.7.0's migration, under its name, so a database imported from v4.7.0 has
// already run it.
return new class extends Migration
{
    public function up(): void
    {
        // 5.0.0-alpha installs have it already, from a 2026_10_23_000000
        // migration this replaced.
        if (Schema::hasColumn('users', 'last_active_at')) {
            return;
        }

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
