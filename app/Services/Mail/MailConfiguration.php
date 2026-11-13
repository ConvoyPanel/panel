<?php

namespace Convoy\Services\Mail;

/**
 * Whether the panel can send mail. 4.x never sent any, so most installs still
 * carry the example settings: a `log` or `array` mailer, an empty SMTP host, or
 * `mailhog`, a container the example .env names but no compose file runs.
 * None of those count, so the panel doesn't queue mail that can only fail.
 */
class MailConfiguration
{
    private const PLACEHOLDER_HOSTS = ['', 'mailhog', 'null'];

    public static function configured(): bool
    {
        $mailer = (string) config('mail.default');
        $transport = (string) config("mail.mailers.{$mailer}.transport");

        return match ($transport) {
            '', 'log', 'array' => false,
            'smtp' => ! in_array(strtolower(trim((string) config("mail.mailers.{$mailer}.host"))), self::PLACEHOLDER_HOSTS, true),
            default => true,
        };
    }
}
