<?php

namespace App\Services\Mail;

use App\Enums\Mail\MailEncryption;
use App\Settings\MailSettings;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

/**
 * Owns the one translation from Convoy's stored shape onto Laravel's mailer config.
 *
 * Everything that sends mail keeps calling `Mail::` and `$user->notify()`
 * untouched; this class only rewrites `config('mail.*')` early enough that the
 * mail manager has not resolved a transport yet. Centralising it matters
 * because the settings screen has to be able to build the *same* transport from
 * an unsaved form to test it, and a second, subtly different mapping between
 * "what we test" and "what we send with" would make the test button a liar.
 */
class MailConfigurator
{
    /** The throwaway mailer name a connection test is sent through. */
    public const TEST_MAILER = 'convoy-settings-test';

    public function __construct(private MailSettings $settings) {}

    /**
     * Whether anything can actually deliver.
     *
     * There is no environment tier for SMTP any more: the install migration imported whatever
     * `MAIL_*` held, so the form is the only place SMTP comes from and a blank host means the
     * panel has no relay. Reading the environment as a fallback here is exactly the second
     * source of truth that was removed.
     */
    public function isConfigured(): bool
    {
        return $this->settings->isConfigured() || $this->managedElsewhere();
    }

    /**
     * Whether delivery is handled by a transport this screen does not own.
     *
     * ses, postmark, resend and mailgun are configured entirely through credentials there are no
     * fields for here, so an install using one is configured — just not by this form. Saying
     * otherwise would send an operator to fix an outage they do not have.
     *
     * `log` and `array` are excluded on purpose: they accept every message and deliver none, so
     * counting them would report that mail works when the only thing receiving it is a file.
     */
    private function managedElsewhere(): bool
    {
        $mailer = (string) config('mail.default');

        return ! in_array($mailer, ['smtp', 'log', 'array', ''], true);
    }

    /**
     * Push the stored settings onto the runtime config.
     *
     * A no-op when no host is stored, which leaves whatever Laravel was already configured
     * with — relevant only for the non-SMTP transports this screen does not manage.
     */
    public function apply(): void
    {
        if (! $this->settings->isConfigured()) {
            return;
        }

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp', $this->transportConfig());
        Config::set('mail.from', [
            'address' => $this->settings->from_address,
            'name' => $this->settings->from_name,
        ]);
    }

    /**
     * Apply, then discard any mailer the manager already built.
     *
     * Only the settings screen needs this: everywhere else the config is in place before the
     * first mailer is resolved, but saving happens mid-request, after something may already
     * hold a mailer built from the previous credentials.
     */
    public function applyAndPurge(): void
    {
        $this->apply();

        Mail::purge('smtp');
    }

    /**
     * The stored settings as a Laravel smtp mailer array.
     *
     * @return array<string, mixed>
     */
    public function transportConfig(): array
    {
        return $this->buildTransportConfig(
            $this->settings->host,
            $this->settings->port,
            $this->settings->username,
            $this->settings->password,
            $this->settings->encryption,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function buildTransportConfig(
        string $host,
        int $port,
        string $username,
        string $password,
        MailEncryption $encryption,
    ): array {
        return [
            'transport' => 'smtp',
            'scheme' => $encryption->scheme(),
            'host' => $host,
            'port' => $port,
            // Empty strings rather than nulls would make Symfony attempt an AUTH
            // handshake against relays that allow unauthenticated submission.
            'username' => $username !== '' ? $username : null,
            'password' => $password !== '' ? $password : null,
            'timeout' => 10,
            'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST) ?: null,
        ];
    }

    /**
     * Send a test message through an ad-hoc mailer built from the given config,
     * bypassing both the stored settings and the queue.
     *
     * Ad-hoc on purpose: the settings screen tests credentials the operator has
     * typed but not yet saved, so this cannot read from storage. Sending it
     * synchronously is equally deliberate — a queued test would report success
     * the moment it was enqueued, which is precisely the failure mode (auth
     * rejected on a worker, hours later, silently) that this screen exists to
     * eliminate.
     *
     * @param  array<string, mixed>  $transport
     *
     * @throws \Throwable the raw transport error, which is the useful part
     */
    public function sendTest(
        string $recipient,
        array $transport,
        string $fromAddress,
        string $fromName,
        MailEncryption $encryption,
    ): void {
        Config::set('mail.mailers.'.self::TEST_MAILER, $transport);

        try {
            Mail::mailer(self::TEST_MAILER)
                ->to($recipient)
                ->send(new TestMailMessage(
                    $fromAddress,
                    $fromName,
                    $transport['host'],
                    $transport['port'],
                    $encryption,
                ));
        } finally {
            // Never leave a transport holding the typed credentials on the manager.
            Mail::purge(self::TEST_MAILER);
            Config::set('mail.mailers.'.self::TEST_MAILER, null);
        }
    }
}
