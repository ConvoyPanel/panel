<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 4.x and 5.x kept their models under `Convoy\Models`, and that class name is what
 * Sanctum stored as each token's `tokenable_type`. They're under `App\Models` now,
 * so an upgraded token's owner no longer resolves: Sanctum throws "Class
 * Convoy\Models\User not found" and every API client, billing modules included,
 * stops authenticating.
 *
 * Tokens are the only polymorphic rows that survive the upgrade. The activity log
 * that also stored these names is dropped by 2026_08_19_000000_create_audit_logs_table.
 */
return new class extends Migration
{
    private const OLD = 'Convoy\\Models\\';

    private const NEW = 'App\\Models\\';

    public function up(): void
    {
        $types = DB::table('personal_access_tokens')->distinct()->pluck('tokenable_type');

        foreach ($types as $type) {
            if (str_starts_with($type, self::OLD)) {
                DB::table('personal_access_tokens')
                    ->where('tokenable_type', $type)
                    ->update(['tokenable_type' => self::NEW.substr($type, strlen(self::OLD))]);
            }
        }
    }

    public function down(): void
    {
        // Nothing to undo. Renaming back would also catch tokens made since, and no
        // code reads the old names.
    }
};
