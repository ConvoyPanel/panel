<?php

namespace Convoy\Jobs\Server;

use Convoy\Models\Server;
use Convoy\Services\Servers\ServerBuildService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class WaitUntilVmIsCreatedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function retryUntil(): Carbon
    {
        return now()->addMinutes(30);
    }

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled()];
    }

    public function __construct(protected int $serverId)
    {
        //
    }

    public function handle(ServerBuildService $service): void
    {
        $server = Server::findOrFail($this->serverId);

        $isCreated = $service->isVmCreated($server);

        if (! $isCreated) {
            $this->release(3);
        }
    }
}
