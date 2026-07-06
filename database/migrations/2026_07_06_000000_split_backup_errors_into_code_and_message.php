<?php

use App\Enums\Server\Backup\BackupErrorCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->string('error_code')->nullable()->after('errors');
            $table->string('error_message')->nullable()->after('error_code');
        });

        // Migrate the old free-text `errors` into the code + message pair:
        // the raw text becomes the message, classified into a stable code.
        DB::table('backups')->whereNotNull('errors')->orderBy('id')
            ->each(function (object $backup) {
                DB::table('backups')->where('id', $backup->id)->update([
                    'error_code' => BackupErrorCode::classify($backup->errors)->value,
                    'error_message' => $backup->errors,
                ]);
            });

        Schema::table('backups', function (Blueprint $table) {
            $table->dropColumn('errors');
        });
    }

    public function down(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->string('errors')->nullable()->after('description');
        });

        // Recombine: prefer the human-readable message, fall back to the code.
        DB::table('backups')->whereNotNull('error_code')->orderBy('id')
            ->each(function (object $backup) {
                DB::table('backups')->where('id', $backup->id)->update([
                    'errors' => $backup->error_message ?? $backup->error_code,
                ]);
            });

        Schema::table('backups', function (Blueprint $table) {
            $table->dropColumn(['error_code', 'error_message']);
        });
    }
};
