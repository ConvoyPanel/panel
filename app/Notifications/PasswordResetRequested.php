<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A link to choose a new password, sent from the sign-in page's "Forgot password?".
 *
 * Like the invite, it carries no credential of its own: the token only works together with the
 * address it was sent to, once, and for as long as the broker's `expire` allows.
 */
class PasswordResetRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(#[\SensitiveParameter] private string $token) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $minutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Reset your '.config('app.name').' password')
            ->view('mail.password-reset', [
                'name' => $notifiable->name,
                'link' => self::url($this->token, $notifiable->getEmailForPasswordReset()),
                'expiry' => $minutes.' '.($minutes === 1 ? 'minute' : 'minutes'),
            ]);
    }

    /**
     * Built against APP_URL for the same reason as the invite link: the request that asked for
     * it may have arrived on a different host than the one the person signs in on.
     */
    public static function url(#[\SensitiveParameter] string $token, string $email): string
    {
        return rtrim((string) config('app.url'), '/')
            .'/auth/reset-password/'.$token
            .'?'.http_build_query(['email' => $email]);
    }
}
