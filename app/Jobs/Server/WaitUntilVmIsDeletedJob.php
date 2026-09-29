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

class WaitUntilVmIsDeletedJob implements ShouldQueue
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

        // Only Proxmox's "does not exist" counts as deleted. Any other error
        // is thrown and retried: treating an outage as "gone" would let the
        // chain drop the server from Convoy while its VM is still there.
        if ($service->vmExists($server)) {
            $this->release(3);
        }
    }

    /**
     * Wait between retries after an error instead of re-polling at once.
     */
    public function backoff(): int
    {
        return 5;
    }
}
