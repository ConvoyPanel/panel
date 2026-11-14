<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * One row, id 1: the telemetry switch and the state of the daily report
     * (docs/telemetry.md).
     */
    public function up(): void
    {
        Schema::create('telemetry_settings', function (Blueprint $table) {
            $table->id();
            // Null until an admin uses the switch; config('convoy.telemetry.default') applies until then.
            $table->boolean('enabled')->nullable();
            // A random UUID that names this panel to the console and nothing else. Never derived
            // from APP_KEY, APP_URL or anything else that could tie a report to a panel's owner.
            $table->uuid('installation_id');
            // When a report last went out, accepted or not. At most one is sent per UTC day.
            $table->timestamp('attempted_at')->nullable();
            // When the console last accepted a report.
            $table->timestamp('reported_at')->nullable();
            $table->timestamps();
        });

        DB::table('telemetry_settings')->insert([
            'id' => 1,
            'enabled' => null,
            'installation_id' => (string) Str::uuid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('telemetry_settings');
    }
};
