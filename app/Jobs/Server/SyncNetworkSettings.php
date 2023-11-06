<?php

namespace Convoy\Jobs\Server;

use Convoy\Models\Server;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Convoy\Services\Servers\NetworkService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;

class SyncNetworkSettings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function retryUntil()
    {
        return now()->addMinutes(5);
    }

    public function __construct(protected int $serverId)
    {
        //
    }

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled, new WithoutOverlapping("server.sync-network-settings#{$this->serverId}")];
    }

    /**
     * Execute the job.
     */
    public function handle(NetworkService $service): void
    {
        $server = Server::findOrFail($this->serverId);

        $service->syncSettings($server);
    }
}
