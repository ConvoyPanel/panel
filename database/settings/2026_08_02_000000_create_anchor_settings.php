<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Middle tier of the panel-URL cascade. See App\Settings\AnchorSettings.
        // Empty means "use APP_URL", which is the previous behaviour.
        $this->migrator->add('anchor.panel_url', '');
    }

    public function down(): void
    {
        $this->migrator->delete('anchor.panel_url');
    }
};
