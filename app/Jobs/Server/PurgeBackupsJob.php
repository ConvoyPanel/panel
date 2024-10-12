<?php

namespace App\Jobs\Server;

use App\Models\Server;
use App\Services\Backups\PurgeBackupsService;
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

    public function __construct(protected int $serverId)
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

        $service->handle($server);
    }
}
