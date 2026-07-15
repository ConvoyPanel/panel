<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backfills `two_factor_confirmed_at`, which Fortify's own two-factor migration
 * only creates `if (Fortify::confirmsTwoFactorAuthentication())`. Convoy shipped
 * with that option off, so every existing install ran that migration without the
 * column — and turning confirmation on made writes to it fatal:
 *
 *   SQLSTATE[42703]: column "two_factor_confirmed_at" of relation "users"
 *   does not exist
 *
 * Editing the original migration would not help anyone who already ran it, hence
 * a separate one. Guarded because a *fresh* install now evaluates that condition
 * as true and creates the column itself before this runs.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'two_factor_confirmed_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('two_factor_confirmed_at')
                    ->after('two_factor_recovery_codes')
                    ->nullable();
            });
        }

        // Anyone who already set two factor up did so under the old rule, where
        // holding a secret *was* being enabled. Now that enabled means
        // confirmed, leaving their timestamp null would read as "no second
        // factor" and quietly stop challenging them at login — a downgrade none
        // of them asked for. Treat an existing secret as already confirmed.
        DB::table('users')
            ->whereNotNull('two_factor_secret')
            ->whereNull('two_factor_confirmed_at')
            ->update(['two_factor_confirmed_at' => now()]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'two_factor_confirmed_at')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('two_factor_confirmed_at');
        });
    }
};
