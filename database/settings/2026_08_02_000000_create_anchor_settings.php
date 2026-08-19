<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Middle tier of the panel-URL cascade. See App\Settings\AnchorSettings.
        // Empty means "use APP_URL", which is the previous behaviour.
        //
        // Guarded rather than a bare `add()`: the property outlives this
        // migration's own row in `migrations` — a settings row is not dropped by
        // rolling the schema back, and a restored snapshot can carry one without
        // the history that created it. `add()` throws on an existing property,
        // which turns that into a hard stop on every later `migrate`.
        if (! $this->migrator->exists('anchor.panel_url')) {
            $this->migrator->add('anchor.panel_url', '');
        }
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('anchor.panel_url');
    }
};
