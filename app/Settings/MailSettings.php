<?php

namespace App\Settings;

use App\Data\Admin\Settings\MailSettingsData;
use App\Enums\Mail\MailEncryption;
use App\Services\Mail\MailConfigurator;
use Spatie\LaravelSettings\Settings;

/**
 * The panel's SMTP credentials, and the only place they come from.
 *
 * It exists because mail is the one dependency an operator cannot debug from
 * inside the panel: a wrong password or a rejected From address fails on a
 * queue worker, hours later, silently. Editing it here means the credentials
 * can be tested against the real relay before they are saved, which is the
 * whole point of moving them out of the environment.
 *
 * `MAIL_*` is read exactly once, by the install migration that imports it into these
 * properties. After that the environment is not consulted: a blank host means the panel has
 * no relay, not that one might be configured somewhere else. That single source is what makes
 * the test button meaningful — there is only one configuration it could be proving.
 *
 * Reads should go through {@see MailConfigurator}, which owns the mapping onto Laravel's
 * mailer config.
 */
class MailSettings extends Settings
{
    /** SMTP hostname. Empty means the panel has no relay and cannot send. */
    public string $host = '';

    public int $port = 587;

    public string $username = '';

    /**
     * The SMTP password. Encrypted at rest — this is the first secret to live
     * in the settings table — and never returned by the API; see
     * {@see MailSettingsData}.
     */
    public string $password = '';

    // Stored as the enum's backing value: laravel-settings resolves an EnumCast for a typed
    // enum property on its own, so nothing needs registering and the column keeps holding the
    // same `tls`/`ssl`/`none` string a plain string property would have written.
    //
    // Line comments rather than a docblock, deliberately. PropertyReflector::resolveType()
    // branches on the *presence* of a doc comment and then takes the type only from an `@var`
    // tag, so wrapping this in `/** */` to explain it resolves the cast to null, the raw string
    // gets assigned to this typed property during load, and the whole group dies on a TypeError
    // that names whichever property happened to be first. A `//` comment is invisible to
    // getDocComment(), which leaves the native type — the real source of truth — in charge.
    public MailEncryption $encryption = MailEncryption::TLS;

    public string $from_address = '';

    public string $from_name = '';

    /**
     * The host is the load-bearing field — without one there is nothing to connect to, so a
     * partially filled form is treated as unset rather than as a broken configuration.
     */
    public function isConfigured(): bool
    {
        return $this->host !== '';
    }

    /**
     * @return array<int, string>
     */
    public static function encrypted(): array
    {
        return ['password'];
    }

    public static function group(): string
    {
        return 'mail';
    }
}
