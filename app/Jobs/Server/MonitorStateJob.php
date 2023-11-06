<?php

namespace Convoy\Jobs\Server;

use Closure;
use Convoy\Models\Server;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Convoy\Enums\Server\State;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Convoy\Repositories\Proxmox\Server\ProxmoxServerRepository;

class MonitorStateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public function retryUntil()
    {
        return now()->addMinutes(2);
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $serverId, protected State $targetState, protected ?Closure $callback = null)
    {
        //
    }

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled];
    }

    /**
     * Execute the job.
     */
    public function handle(ProxmoxServerRepository $repository): void
    {
        $server = Server::findOrFail($this->serverId);

        $stateData = $repository->setServer($server)->getState();

        if ($stateData->state === $this->targetState) {
            if ($this->callback !== null) {
                call_user_func($this->callback);
            }
        } else {
            $this->release(3);
        }
    }
}
