<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A database imported from v4.7.0 already has it, from 2026_11_13_000001.
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
        if (! Schema::hasColumn('users', 'last_active_at')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_active_at');
        });
    }
};
