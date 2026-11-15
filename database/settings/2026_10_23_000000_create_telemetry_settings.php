<?php

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // A panel imported from v4.7.0 brings its switch and id in v4's own table, with null
        // meaning v4's default, which was on.
        $v4 = Schema::hasTable('telemetry_settings') ? DB::table('telemetry_settings')->find(1) : null;

        // Guarded rather than a bare `add()`, like the other settings migrations: a settings row
        // outlives a schema rollback, and `add()` throws on a property that already exists.
        if (! $this->migrator->exists('telemetry.enabled')) {
            $this->migrator->add('telemetry.enabled', (bool) ($v4->enabled ?? true));
        }

        if (! $this->migrator->exists('telemetry.installation_id')) {
            $this->migrator->add('telemetry.installation_id', $v4->installation_id ?? (string) Str::uuid());
        }

        if (! $this->migrator->exists('telemetry.reported_at')) {
            $this->migrator->add('telemetry.reported_at', isset($v4->reported_at) ? CarbonImmutable::parse($v4->reported_at) : null);
        }

        if (! $this->migrator->exists('telemetry.counting_since')) {
            $this->migrator->add('telemetry.counting_since', isset($v4->created_at) ? CarbonImmutable::parse($v4->created_at) : CarbonImmutable::now());
        }

        Schema::dropIfExists('telemetry_settings');
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('telemetry.enabled');
        $this->migrator->deleteIfExists('telemetry.installation_id');
        $this->migrator->deleteIfExists('telemetry.reported_at');
        $this->migrator->deleteIfExists('telemetry.counting_since');
    }
};
