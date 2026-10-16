<?php

namespace Convoy\Jobs\Server;

use Convoy\Enums\Server\PowerAction;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxPowerRepository;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Convoy\Jobs\Concerns\LocksPerSubject;
use Illuminate\Queue\SerializesModels;

class SendPowerCommandJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable, LocksPerSubject;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(protected int $serverId, protected PowerAction $power)
    {
    }

    /**
     * Wait before retrying a failed Proxmox call instead of retrying at once.
     */
    public function backoff(): array
    {
        return [5, 15];
    }

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled(), $this->lockPerSubject($this->serverId)];
    }

    public function handle(ProxmoxPowerRepository $repository): void
    {
        $server = Server::findOrFail($this->serverId);

        $repository->setServer($server)->send($this->power);
    }
}
