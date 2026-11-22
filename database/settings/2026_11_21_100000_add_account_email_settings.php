<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Password reset starts off: it turns the mailbox into a way into the account, and an
        // operator whose users sign in through something else should not find that switched on
        // by an upgrade. The server-ready email starts on, since it is the one the panel
        // previously had no way to send at all.
        //
        // Guarded for the same reason as the migration that created the group: a settings row
        // outlives a schema rollback, and `add()` throws on one that already exists.
        if (! $this->migrator->exists('account.allow_password_reset')) {
            $this->migrator->add('account.allow_password_reset', false);
        }

        if (! $this->migrator->exists('account.send_server_ready_email')) {
            $this->migrator->add('account.send_server_ready_email', true);
        }
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('account.allow_password_reset');
        $this->migrator->deleteIfExists('account.send_server_ready_email');
    }
};
