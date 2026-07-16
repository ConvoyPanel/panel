<?php

namespace App\Jobs\Server;

use App\Exceptions\Proxmox\RequestException;
use App\Models\DeploymentStep;
use App\Services\Servers\ServerBuildService;
use App\Traits\Jobs\FailsWithStep;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class DeleteServerJob implements ShouldQueue
{
    use Batchable, Dispatchable, FailsWithStep, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 20;

    public function __construct(
        #[WithoutRelations]
        public DeploymentStep $step,
    ) {}

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled, new WithoutOverlapping(
            (string) $this->step->deployment->server->id,
        )];
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function handle(ServerBuildService $service): void
    {
        $this->step->start();

        $service->delete($this->step->deployment->server);
    }
}
