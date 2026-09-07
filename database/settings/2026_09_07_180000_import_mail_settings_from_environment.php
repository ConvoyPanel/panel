<?php

use App\Enums\Mail\MailEncryption;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    /**
     * Adopt whatever `MAIL_*` already configured, so the screen is the source of truth.
     *
     * The first migration deliberately wrote blanks and left the environment in charge, which
     * gave the panel two places mail could come from and a permanent banner explaining which one
     * was winning. That banner was the architecture leaking into the UI. Importing once collapses
     * the two tiers into one: the form always shows what is actually being used, so an empty host
     * means "nothing is set" rather than "something might be set somewhere else".
     *
     * Import, not a live read — after this runs, `MAIL_*` is no longer consulted for anything the
     * screen shows. That is the trade, and it is why the values are copied rather than mirrored.
     */
    public function up(): void
    {
        // Nothing to import into: the settings group has to exist first.
        if (! $this->migrator->exists('mail.host')) {
            return;
        }

        $imported = $this->environmentSmtp();

        // `update()` hands the closure the stored payload, which is the only way to read a
        // property through the migrator — and reading it is what decides every field below.
        $wasBlank = false;

        $this->migrator->update('mail.host', function ($current) use (&$wasBlank, $imported) {
            $wasBlank = ($current === '' || $current === null);

            return $wasBlank && $imported !== null ? $imported['host'] : $current;
        });

        // An operator who already filled the screen in owns it; never overwrite that.
        if (! $wasBlank || $imported === null) {
            return;
        }

        $this->migrator->update('mail.port', fn () => $imported['port']);
        $this->migrator->update('mail.username', fn () => $imported['username']);
        $this->migrator->update('mail.encryption', fn () => $imported['encryption']);
        $this->migrator->update('mail.from_address', fn () => $imported['from_address']);
        $this->migrator->update('mail.from_name', fn () => $imported['from_name']);
        $this->migrator->updateEncrypted('mail.password', fn () => $imported['password']);
    }

    /**
     * The environment's SMTP settings, or null when there is nothing worth importing.
     *
     * @return array<string, mixed>|null
     */
    private function environmentSmtp(): ?array
    {
        // Only smtp is importable. `log` and `array` deliver nothing, and ses/postmark/resend are
        // configured out of band through credentials this screen has no fields for — importing a
        // host from those would be inventing one.
        if (config('mail.default') !== 'smtp') {
            return null;
        }

        $host = (string) config('mail.mailers.smtp.host', '');
        $port = (int) config('mail.mailers.smtp.port', 587);

        if ($host === '') {
            return null;
        }

        // Laravel's own fallback when neither variable is set is 127.0.0.1:2525. Importing that
        // pair would turn an install that never configured mail into one that looks configured
        // and silently fails, which is the single most misleading outcome available here.
        if ($host === '127.0.0.1' && $port === 2525) {
            return null;
        }

        return [
            'host' => $host,
            'port' => $port,
            'username' => (string) config('mail.mailers.smtp.username', ''),
            'password' => (string) config('mail.mailers.smtp.password', ''),
            'encryption' => $this->encryptionFor($port)->value,
            'from_address' => (string) config('mail.from.address', ''),
            'from_name' => (string) config('mail.from.name', ''),
        ];
    }

    /**
     * Laravel expresses implicit TLS as the `smtps` scheme; older installs express it as port 465
     * alone. Either is enough to mean "encrypted from the first byte".
     */
    private function encryptionFor(int $port): MailEncryption
    {
        $scheme = (string) config('mail.mailers.smtp.scheme', '');

        return ($scheme === 'smtps' || $port === 465)
            ? MailEncryption::SSL
            : MailEncryption::TLS;
    }

    public function down(): void
    {
        // Nothing: this migration copies values into properties another migration owns. Undoing
        // it would mean knowing which of them the operator has edited since, and guessing wrong
        // would silently stop an install's mail.
    }
};
