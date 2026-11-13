<?php

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Guarded rather than a bare `add()`, like the other settings migrations: a settings row
        // outlives a schema rollback, and `add()` throws on a property that already exists.
        if (! $this->migrator->exists('telemetry.enabled')) {
            $this->migrator->add('telemetry.enabled', true);
        }

        if (! $this->migrator->exists('telemetry.installation_id')) {
            $this->migrator->add('telemetry.installation_id', (string) Str::uuid());
        }

        if (! $this->migrator->exists('telemetry.reported_at')) {
            $this->migrator->add('telemetry.reported_at', null);
        }

        if (! $this->migrator->exists('telemetry.counting_since')) {
            $this->migrator->add('telemetry.counting_since', CarbonImmutable::now());
        }
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('telemetry.enabled');
        $this->migrator->deleteIfExists('telemetry.installation_id');
        $this->migrator->deleteIfExists('telemetry.reported_at');
        $this->migrator->deleteIfExists('telemetry.counting_since');
    }
};
