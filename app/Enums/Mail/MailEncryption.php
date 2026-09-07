<?php

namespace App\Enums\Mail;

/**
 * How the panel secures its connection to the SMTP relay.
 *
 * These are the words an operator recognises from every other mail client, which
 * is why they are stored rather than Symfony's URI schemes: the value in the
 * database should mean something to the person who typed it.
 */
enum MailEncryption: string
{
    /** STARTTLS — connect in the clear, then upgrade. The submission default (587). */
    case TLS = 'tls';

    /** Implicit TLS — encrypted from the first byte. Classically port 465. */
    case SSL = 'ssl';

    /** No upgrade requested. Symfony may still negotiate STARTTLS opportunistically. */
    case NONE = 'none';

    /**
     * The Symfony/Laravel transport scheme this maps onto.
     *
     * Only implicit TLS gets its own scheme. `tls` and `none` both open a plaintext
     * connection and differ solely in whether STARTTLS is then negotiated — which
     * Symfony attempts whenever the server advertises it — so they share `smtp`,
     * and `none` is honest about being a preference rather than a guarantee.
     */
    public function scheme(): string
    {
        return $this === self::SSL ? 'smtps' : 'smtp';
    }

    /**
     * How the mode is named on the settings screen, minus the port hint the
     * select adds. Mail that reports a configuration should use the operator's
     * own words for it, not the stored `tls`/`ssl`/`none`.
     */
    public function label(): string
    {
        return match ($this) {
            self::TLS => 'STARTTLS',
            self::SSL => 'Implicit TLS',
            self::NONE => 'None',
        };
    }

    /** The port operators expect for this mode, used to prefill the form. */
    public function defaultPort(): int
    {
        return match ($this) {
            self::SSL => 465,
            self::TLS => 587,
            self::NONE => 25,
        };
    }
}
