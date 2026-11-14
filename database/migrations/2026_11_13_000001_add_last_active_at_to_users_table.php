<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// v4.7.0's migration, kept so a database imported from it has a file for every
// migration it ran. 2026_10_23_000000 adds the same column on everything else.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'last_active_at')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_active_at')->nullable()->index()->after('remember_token');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'last_active_at')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['last_active_at']);
            $table->dropColumn('last_active_at');
        });
    }
};
