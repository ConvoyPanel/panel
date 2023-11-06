<?php

namespace Convoy\Jobs\Server;

use Convoy\Models\Server;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Convoy\Services\Servers\ServerBuildService;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;

class WaitUntilVmIsDeletedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function retryUntil()
    {
        return now()->addMinutes(30);
    }

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled];
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $serverId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(ServerBuildService $service): void
    {
        $server = Server::findOrFail($this->serverId);

        $isDeleted = $service->isVmDeleted($server);

        if (! $isDeleted) {
            $this->release(3);
        }
    }
}
