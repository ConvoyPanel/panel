<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // All three permissive, because the account surface they govern shipped first: an existing
        // install that upgrades into this migration must find its users able to do exactly what
        // they could yesterday. Restricting is the deliberate act, not the default.
        //
        // Guarded rather than a bare `add()` for the same reason as the anchor and audit
        // migrations — a settings row outlives the schema rollback that created it, and `add()`
        // throws on an existing property, which would hard-stop every later `migrate`.
        foreach ([
            'account.allow_name_change',
            'account.allow_email_change',
            'account.allow_password_change',
        ] as $property) {
            if (! $this->migrator->exists($property)) {
                $this->migrator->add($property, true);
            }
        }
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('account.allow_name_change');
        $this->migrator->deleteIfExists('account.allow_email_change');
        $this->migrator->deleteIfExists('account.allow_password_change');
    }
};
