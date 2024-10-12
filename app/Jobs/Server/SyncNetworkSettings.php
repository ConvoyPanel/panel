<?php

namespace App\Jobs\Server;

use App\Models\Server;
use App\Services\Servers\NetworkService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class SyncNetworkSettings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function retryUntil(): Carbon
    {
        return now()->addMinutes(5);
    }

    public function __construct(protected int $serverId)
    {
    }

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled(), new WithoutOverlapping(
            "server.sync-network-settings#$this->serverId",
        )];
    }

    public function handle(NetworkService $service): void
    {
        $server = Server::findOrFail($this->serverId);

        $service->syncSettings($server);
    }
}
