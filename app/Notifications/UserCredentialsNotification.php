<?php

namespace Convoy\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserCredentialsNotification extends Notification
{
    public function __construct(private string $password) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Convoy account is ready')
            ->greeting("Hello {$notifiable->name},")
            ->line('A Convoy account has been created for you.')
            ->line("Email: {$notifiable->email}")
            ->line("Password: {$this->password}")
            ->action('Sign in to Convoy', url('/'));
    }
}
