<?php

namespace Convoy\Notifications;

use Convoy\Models\Server;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ServerCredentialsNotification extends Notification
{
    public function __construct(
        private Server $server,
        private string $username,
        private string $password,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->server->name} is ready")
            ->greeting("Hello {$notifiable->name},")
            ->line('Your server has finished installing and is ready to use.')
            ->line("Server: {$this->server->name}")
            ->line("Hostname: {$this->server->hostname}")
            ->line("Username: {$this->username}")
            ->line("Password: {$this->password}")
            ->action('Open server', url("/servers/{$this->server->uuid_short}"));
    }
}
