<?php

namespace Convoy\Jobs\Server;

use Convoy\Models\Server;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Convoy\Enums\Server\PowerAction;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Convoy\Repositories\Proxmox\Server\ProxmoxPowerRepository;

class SendPowerCommandJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public $timeout = 15;

    public $tries = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $serverId, protected PowerAction $power)
    {
        //
    }

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled, new WithoutOverlapping("server.send-power-command#{$this->serverId}")];
    }

    /**
     * Execute the job.
     */
    public function handle(ProxmoxPowerRepository $repository): void
    {
        $server = Server::findOrFail($this->serverId);

        $repository->setServer($server)->send($this->power);
    }
}
