<?php

namespace Convoy\Jobs\Server;

use Convoy\Models\Server;
use Convoy\Services\Mail\CredentialNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class SendServerCredentialsEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 10;

    public function __construct(
        protected int $serverId,
        protected string $username,
        protected string $password,
    ) {}

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled, new WithoutOverlapping(
            "server.credentials-email#{$this->serverId}",
        )];
    }

    public function handle(CredentialNotificationService $service): void
    {
        $server = Server::with('user')->find($this->serverId);

        if (! $server) {
            return;
        }

        $service->sendServerCredentials($server, $this->username, $this->password);
    }
}
