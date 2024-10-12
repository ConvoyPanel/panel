<?php

namespace App\Jobs\Server;

use Closure;
use App\Enums\Server\State;
use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxServerRepository;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class MonitorStateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public function retryUntil(): Carbon
    {
        return now()->addMinutes(2);
    }

    public function __construct(
        protected int      $serverId,
        protected State    $targetState,
        protected ?Closure $callback = null,
    ) {
        //
    }

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled()];
    }

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
