<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Global tier of the overage-penalty cascade. See
        // docs/bandwidth-rate-limiting-plan.md §5.3 and App\Settings\BandwidthSettings.
        $this->migrator->add('bandwidth.overage_action', 'throttle'); // 'throttle' | 'disconnect'
        $this->migrator->add('bandwidth.overage_rate', 1_000_000);    // bytes/s (1 MB/s)
    }

    public function down(): void
    {
        $this->migrator->delete('bandwidth.overage_rate');
        $this->migrator->delete('bandwidth.overage_action');
    }
};
