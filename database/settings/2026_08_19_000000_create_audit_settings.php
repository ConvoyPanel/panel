<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Default to masking: a public host leaking which staff member touched a customer's server
        // is the worse failure of the two, and an internal deployment can flip it in one click.
        //
        // Guarded rather than a bare `add()` for the same reason as the anchor settings migration —
        // a settings row outlives the schema rollback that created it, and `add()` throws on an
        // existing property, which would hard-stop every later `migrate`.
        if (! $this->migrator->exists('audit.reveal_staff_identity')) {
            $this->migrator->add('audit.reveal_staff_identity', false);
        }
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('audit.reveal_staff_identity');
    }
};
