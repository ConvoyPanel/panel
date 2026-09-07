<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Seeded blank rather than from the current MAIL_* variables, which is the deliberate
        // choice: copying the environment in would make the database the source of truth the
        // moment this migration ran, and a later edit to .env would then be silently ignored by
        // an operator who never opened the new screen. Blank means "not set here", so delivery
        // on an upgraded install is byte-for-byte what it was yesterday until someone fills the
        // form in. Same cascade idiom as anchor.panel_url.
        //
        // Guarded rather than a bare `add()` for the reason the anchor, audit and account
        // migrations are: a settings row outlives the schema rollback that created it, and
        // `add()` throws on an existing property, which hard-stops every later `migrate`.
        foreach ([
            'mail.host' => '',
            'mail.port' => 587,
            'mail.username' => '',
            'mail.encryption' => 'tls',
            'mail.from_address' => '',
            'mail.from_name' => '',
        ] as $property => $default) {
            if (! $this->migrator->exists($property)) {
                $this->migrator->add($property, $default);
            }
        }

        // Encrypted, so it goes in through the migrator's encrypted path rather than as a plain
        // default. Blank is still blank — there is nothing to encrypt yet — but registering it
        // this way keeps the property's cast consistent from the first write.
        if (! $this->migrator->exists('mail.password')) {
            $this->migrator->addEncrypted('mail.password', '');
        }
    }

    public function down(): void
    {
        foreach ([
            'mail.host',
            'mail.port',
            'mail.username',
            'mail.password',
            'mail.encryption',
            'mail.from_address',
            'mail.from_name',
        ] as $property) {
            $this->migrator->deleteIfExists($property);
        }
    }
};
