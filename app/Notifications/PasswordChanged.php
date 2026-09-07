<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Out-of-band notice that an account's password changed.
 *
 * This is the tripwire for an account takeover: an attacker who reaches the change endpoint holds
 * the session and the old password, so every in-app signal is already theirs to suppress. Mail is
 * the one channel they do not control, which is why this sends even though the change was
 * authenticated — "it was authenticated" is exactly the case worth reporting.
 */
class PasswordChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private ?string $ipAddress = null) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // The template decides whether the address gets a row, because whether
        // it was recorded is a display question. Passing null through is enough.
        return (new MailMessage)
            ->subject('Your '.config('app.name').' password was changed')
            ->view('mail.password-changed', [
                'ipAddress' => $this->ipAddress,
            ]);
    }
}
