<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Stops an upgrade straight from v4. A v4 database reaches this schema through
 * 5.x: `convoy import-v4` moves it onto Postgres and runs 5.x's migrations, and
 * 5.x upgrades to this release. Skipping 5.x skips both.
 *
 * Dated just before 2022_12_14_083707_create_settings_table, the first migration
 * 5.x added to v4's, so it runs before anything changes. A database that ran
 * migrations dated after this file but not that one came from v4 without 5.x.
 * A fresh install hasn't reached those yet, and a 5.x database has the settings
 * table already.
 */
return new class extends Migration
{
    private const SELF = '2022_12_14_083706_require_convoy_5';

    private const FIRST_5X_MIGRATION = '2022_12_14_083707_create_settings_table';

    public function up(): void
    {
        $ran = DB::table('migrations')->pluck('migration');

        $existing = $ran->contains(fn (string $migration) => strcmp($migration, self::SELF) > 0);

        if ($existing && ! $ran->contains(self::FIRST_5X_MIGRATION)) {
            throw new RuntimeException(
                "This database is from v4, which upgrades through 5.x. Import it into 5.x with 'convoy import-v4', then upgrade to this release. Nothing was changed."
            );
        }
    }

    public function down(): void
    {
        //
    }
};
