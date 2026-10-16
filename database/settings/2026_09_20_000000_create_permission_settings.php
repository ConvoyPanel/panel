<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Off on upgrade: an install that has never had guest accounts should not acquire the
        // ability to create them without somebody deciding to.
        $this->migrator->add('permissions.allow_guest_accounts', false);
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('permissions.allow_guest_accounts');
    }
};
