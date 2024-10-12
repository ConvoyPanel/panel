<?php

namespace App\Jobs\Server;

use App\Models\Server;
use App\Services\Servers\SyncBuildService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class SyncBuildJob implements ShouldQueue
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
            "server.sync#{$this->serverId}",
        )];
    }

    public function handle(SyncBuildService $service): void
    {
        $server = Server::findOrFail($this->serverId);

        $service->handle($server);
    }
}
