<?php

namespace Convoy\Jobs\Server;

use Convoy\Models\Server;
use Convoy\Services\Servers\ServerAuthService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Convoy\Jobs\Concerns\LocksPerSubject;
use Illuminate\Queue\SerializesModels;

class UpdatePasswordJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LocksPerSubject;

    public int $tries = 15;

    public int $timeout = 60;

    public function backoff(): int
    {
        return 30;
    }

    public function __construct(protected int $serverId, protected string $password)
    {
        //
    }

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled(), $this->lockPerSubject($this->serverId)];
    }

    public function handle(ServerAuthService $service): void
    {
        $server = Server::findOrFail($this->serverId);

        $service->updatePassword($server, $this->password);
    }
}
