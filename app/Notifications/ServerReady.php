<?php

namespace App\Notifications;

use App\Models\Server;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a server's owner that its first install has finished.
 *
 * Sent the same way whether the server was created on the panel or through the API by a billing
 * module, which is what ConvoyPanel/panel#55 asked for. It carries where to reach the server and
 * a link to it in the panel, and no credentials: whoever set the root password already has it,
 * and the panel can reset it.
 */
class ServerReady extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private Server $server) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $server = $this->server->loadMissing(['primaryIPv4Address', 'primaryIPv6Address']);

        return (new MailMessage)
            ->subject($server->name.' is ready')
            ->view('mail.server-ready', [
                'name' => $notifiable->name,
                'serverName' => $server->name,
                'hostname' => $server->hostname,
                'ipv4' => $server->primaryIPv4Address?->ip,
                'ipv6' => $server->primaryIPv6Address?->ip,
                'link' => rtrim((string) config('app.url'), '/').'/servers/'.$server->uuid_short,
            ]);
    }
}
