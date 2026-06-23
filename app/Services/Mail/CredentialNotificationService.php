<?php

namespace Convoy\Services\Mail;

use Convoy\Models\Server;
use Convoy\Models\User;
use Convoy\Notifications\ServerCredentialsNotification;
use Convoy\Notifications\UserCredentialsNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

class CredentialNotificationService
{
    public function sendUserCredentials(User $user, string $password): void
    {
        if (! config('convoy.credentials_mail.users.enabled')) {
            return;
        }

        $this->notifySafely(
            $user,
            new UserCredentialsNotification($password),
            ['type' => 'user_credentials', 'user_id' => $user->id],
        );
    }

    public function sendServerCredentials(Server $server, string $username, string $password): void
    {
        if (! config('convoy.credentials_mail.servers.enabled') || $password === '') {
            return;
        }

        $server->loadMissing('user');

        if (! $server->user) {
            return;
        }

        $this->notifySafely(
            $server->user,
            new ServerCredentialsNotification($server, $username, $password),
            [
                'type' => 'server_credentials',
                'server_id' => $server->id,
                'user_id' => $server->user->id,
            ],
        );
    }

    private function notifySafely(User $user, Notification $notification, array $context): void
    {
        try {
            $user->notify($notification);
        } catch (Throwable $exception) {
            Log::warning('Failed to send credential notification.', [
                ...$context,
                'exception' => $exception,
            ]);
        }
    }
}
