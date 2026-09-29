<?php

namespace Convoy\Jobs\Server;

use Convoy\Models\Server;
use Convoy\Services\Backups\PurgeBackupsService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class PurgeBackupsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public int $tries = 3;

    public int $timeout = 300;

    /**
     * @param bool $force also delete locked backups, as deleting the server does
     */
    public function __construct(protected int $serverId, protected bool $force = false)
    {
        //
    }

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled(), new WithoutOverlapping(
            "server:backups.purge#{$this->serverId}",
        )];
    }

    public function handle(PurgeBackupsService $service): void
    {
        $server = Server::findOrFail($this->serverId);

        $service->handle($server, $this->force);
    }
}
