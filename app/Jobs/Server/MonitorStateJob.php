<?php

namespace App\Jobs\Server;

use App\Enums\Server\State;
use App\Models\Server;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\Attributes\WithoutRelations;
use App\Exceptions\Repository\Proxmox\RequestException;
use App\Repositories\Proxmox\Server\ProxmoxServerRepository;
use Closure;
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
        #[WithoutRelations]
        public Server $server,
        public State $targetState,
    ) {
        //
    }

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled()];
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function handle(ProxmoxServerRepository $repository): void
    {
        $stateData = $repository->setServer($this->server)->getState();

        if ($stateData->state !== $this->targetState) {
            $this->release(3);
        }
    }
}
