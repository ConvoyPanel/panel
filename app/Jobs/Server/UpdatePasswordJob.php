<?php

namespace App\Jobs\Server;

use App\Models\Server;
use App\Services\Servers\ServerAuthService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class UpdatePasswordJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 10;

    public function __construct(protected int $serverId, protected string $password)
    {
        //
    }

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled(), new WithoutOverlapping(
            "server.update-password#{$this->serverId}",
        )];
    }

    public function handle(ServerAuthService $service): void
    {
        $server = Server::findOrFail($this->serverId);

        $service->updatePassword($server, $this->password);
    }
}
