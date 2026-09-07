<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Permissive, for the same reason the other three are: pictures were self-service before
        // this switch existed, and an upgrade must not quietly take that away.
        //
        // Its own migration rather than an edit to the one that created the group — that one has
        // already run everywhere, so a property added to it would never be written.
        if (! $this->migrator->exists('account.allow_avatar_change')) {
            $this->migrator->add('account.allow_avatar_change', true);
        }
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('account.allow_avatar_change');
    }
};
