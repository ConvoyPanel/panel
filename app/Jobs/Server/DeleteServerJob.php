<?php

namespace App\Jobs\Server;

use App\Models\Server;
use App\Models\DeploymentStep;
use App\Traits\Jobs\FailsWithStep;
use App\Enums\Server\DeploymentStatus;
use App\Services\Servers\ServerBuildService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use App\Exceptions\Repository\Proxmox\RequestException;
use function now;

class DeleteServerJob implements ShouldQueue
{
    use Dispatchable, FailsWithStep, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public int $tries = 3;

    public int $timeout = 20;

    public function __construct(
        #[WithoutRelations]
        public DeploymentStep $step,
    )
    {
    }

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled(), new WithoutOverlapping(
            $this->step->deployment->server->id,
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
