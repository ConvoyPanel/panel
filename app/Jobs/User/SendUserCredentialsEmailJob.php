<?php

namespace Convoy\Jobs\User;

use Convoy\Models\User;
use Convoy\Services\Mail\CredentialNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendUserCredentialsEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected int $userId, protected string $password) {}

    public function handle(CredentialNotificationService $service): void
    {
        $user = User::findOrFail($this->userId);

        $service->sendUserCredentials($user, $this->password);
    }
}
