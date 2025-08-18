<?php

namespace App\Jobs\Server;

use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\PowerCommand;
use App\Traits\Jobs\FailsWithStep;
use App\Models\DeploymentStep;
use App\Repositories\Proxmox\Server\ProxmoxPowerRepository;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Throwable;

use function now;

class SendPowerCommandJob implements ShouldQueue
{
    use Batchable, Dispatchable, FailsWithStep, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 15;

    public function __construct(
        #[WithoutRelations]
        public DeploymentStep $step,
        public PowerCommand $power,
    ) {}

    public function middleware(): array
    {
        return [
            new SkipIfBatchCancelled,
            new WithoutOverlapping($this->step->deployment->server->id),
        ];
    }

    public function handle(ProxmoxPowerRepository $repository): void
    {
        $this->step->start();

        $repository->setServer($this->step->deployment->server)->send($this->power);
    }
}
