<?php

namespace App\Jobs\Server;

use App\Enums\Server\PowerAction;
use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxPowerRepository;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class SendPowerCommandJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public int $tries = 3;

    public int $timeout = 15;

    public function __construct(protected int $serverId, protected PowerAction $power)
    {
    }

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled(), new WithoutOverlapping(
            "server.send-power-command#{$this->serverId}",
        )];
    }

    public function handle(ProxmoxPowerRepository $repository): void
    {
        $server = Server::findOrFail($this->serverId);

        $repository->setServer($server)->send($this->power);
    }
}
