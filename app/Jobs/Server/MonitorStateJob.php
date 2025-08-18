<?php

namespace App\Jobs\Server;

use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\State;
use App\Exceptions\Repository\Proxmox\RequestException;
use App\Traits\Jobs\FailsWithStep;
use App\Models\DeploymentStep;
use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxServerRepository;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Throwable;

use function now;

class MonitorStateJob implements ShouldQueue
{
    use Batchable, Dispatchable, FailsWithStep, InteractsWithQueue, Queueable, SerializesModels;

    public function retryUntil(): Carbon
    {
        return now()->addMinutes(2);
    }

    public function __construct(
        #[WithoutRelations]
        public DeploymentStep $step,
        public State $targetState,
    ) {
        //
    }

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled];
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function handle(ProxmoxServerRepository $repository): void
    {
        $stateData = $repository->setServer($this->step->deployment->server)->getState();

        if ($stateData->state === $this->targetState) {
            $this->step->complete();
        } else {
            $this->release(1);
        }
    }
}
