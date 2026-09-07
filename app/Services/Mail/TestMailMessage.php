<?php

namespace App\Services\Mail;

use App\Enums\Mail\MailEncryption;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * The message a connection test sends.
 *
 * Deliberately a real, rendered email rather than a bare SMTP handshake: the
 * failures that actually cost operators an afternoon are authentication being
 * rejected, TLS failing to negotiate, and the relay refusing the From address —
 * none of which a socket connect will surface, and the last of which needs a
 * message with a real sender on it to provoke at all.
 *
 * The From is passed in rather than read from config because the test runs
 * against a form that has not been saved.
 */
class TestMailMessage extends Mailable
{
    public function __construct(
        private string $fromAddress,
        private string $fromName,
        private string $host,
        private int $port,
        private MailEncryption $encryption,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->fromAddress, $this->fromName),
            subject: config('app.name').' mail configuration test',
        );
    }

    public function content(): Content
    {
        // A real view rather than a raw string so the test also exercises the
        // rendering pipeline — a mail config that connects but cannot render is
        // still a mail config that will not deliver.
        //
        // The settings are echoed back into the body because the useful answer
        // is not "something arrived" but "*these* credentials are the ones that
        // worked". An operator testing a host change against a stale saved
        // password gets to see which of the two the relay accepted.
        return new Content(
            view: 'mail.test',
            with: [
                'host' => $this->host,
                'port' => (string) $this->port,
                'encryption' => $this->encryption->label(),
                'fromAddress' => $this->fromAddress,
            ],
        );
    }
}
