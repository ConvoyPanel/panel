<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The invitation to set a first password.
 *
 * Note what this does not contain: a password. That is the entire point — see
 * ConvoyPanel/panel#55, which asked for credentials by email. A link expires, can be revoked,
 * and leaves the mailbox holding nothing that still works once it has been used.
 */
class UserInvited extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $link,
        private int $expiresInDays,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $days = $this->expiresInDays;

        // A built view rather than MailMessage's line/action builder: that
        // builder renders through Laravel's markdown theme, which is the one
        // thing in the app that does not use the panel's design tokens. See
        // emails/ for the Maizzle project that compiles this template.
        return (new MailMessage)
            ->subject('Set up your '.config('app.name').' account')
            ->view('mail.user-invited', [
                'name' => $notifiable->name,
                'link' => $this->link,
                'expiry' => $days.' '.($days === 1 ? 'day' : 'days'),
            ]);
    }
}
