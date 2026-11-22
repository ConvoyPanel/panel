<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The name of the API token behind an entry, copied when the entry is written.
 *
 * `api_token_id` goes null when the token is deleted, and revoking a token deletes it. Without a
 * copy of the name, everything a leaked key did would lose its attribution the moment the key was
 * revoked, which is exactly when someone goes looking. Same reasoning as `actor_label`.
 *
 * Entries whose token still exists get its current name. Entries whose token is already gone have
 * nothing left to copy and stay null.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('api_token_name')->nullable()->after('api_token_id');
        });

        DB::table('audit_logs')
            ->whereNotNull('api_token_id')
            ->update([
                'api_token_name' => DB::table('personal_access_tokens')
                    ->select('name')
                    ->whereColumn('personal_access_tokens.id', 'audit_logs.api_token_id'),
            ]);
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn('api_token_name');
        });
    }
};
