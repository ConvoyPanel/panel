<?php

namespace App\Data\Admin\Settings;

use App\Enums\Mail\MailEncryption;
use Spatie\LaravelData\Data;

/**
 * The mail configuration as the admin Settings screen sees it.
 *
 * Note what is absent: the password. It is write-only across this API — the
 * screen is told *whether* one is stored, never what it is, so an admin session
 * cannot be used to read back a relay credential that was typed by someone
 * else. `passwordSet` is what lets the form show a filled placeholder and send
 * nothing when the field is left alone.
 *
 * `configured` is not derivable from the fields above: an install delivering through ses or
 * postmark has no host to show here and is still perfectly able to send. It exists for the
 * screens that warn about mail being off, not for this one.
 */
class MailSettingsData extends Data
{
    public function __construct(
        public string $host,
        public int $port,
        public string $username,
        public MailEncryption $encryption,
        public string $fromAddress,
        public string $fromName,
        public bool $passwordSet,
        /** Whether anything can actually deliver — this form, or a transport it does not own. */
        public bool $configured,
    ) {}
}
