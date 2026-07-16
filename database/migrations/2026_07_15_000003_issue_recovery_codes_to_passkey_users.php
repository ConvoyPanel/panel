<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\RecoveryCode;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNull('two_factor_recovery_codes')
            ->whereExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('passkeys')
                ->whereColumn('passkeys.user_id', 'users.id'))
            ->orderBy('id')
            ->eachById(function ($user) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(
                            json_encode(Collection::times(8, fn () => RecoveryCode::generate())->all()),
                        ),
                    ]);
            });
    }

    /** Recovery codes are user-held secrets; a rollback must not silently revoke them. */
    public function down(): void
    {
        // Irreversible data migration.
    }
};
