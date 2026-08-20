<?php

namespace App\Jobs\Server;

use App\Exceptions\Proxmox\RequestException;
use App\Models\DeploymentStep;
use App\Services\Servers\ServerBuildService;
use App\Traits\HandlesProxmoxErrors;
use App\Traits\Jobs\FailsWithStep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

use function now;

/**
 * Owns the `delete-vm` step: it issues the destroy exactly once and then polls
 * until Proxmox no longer reports the guest. A VM that is already gone counts as
 * deleted, so a nonexistent-VM error on the destroy completes the step.
 */
class DeleteVmJob implements ShouldQueue
{
    use Dispatchable, FailsWithStep, HandlesProxmoxErrors, InteractsWithQueue, Queueable, SerializesModels;

    public function retryUntil(): Carbon
    {
        return now()->addMinutes(30);
    }

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled];
    }

    public function __construct(
        #[WithoutRelations]
        public DeploymentStep $step,
    ) {}

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function handle(ServerBuildService $service): void
    {
        $server = $this->step->deployment->server;

        try {
            $this->step->kickOnce(fn () => $service->delete($server));
        } catch (RequestException $e) {
            if (! $this->isNonexistentVMError($e)) {
                throw $e;
            }

            $this->logSwallowedNonexistentVM($server, 'delete');

            // Already gone is already deleted.
            $this->step->markCompleted();

            return;
        }

        if ($service->isVmDeleted($server)) {
            $this->step->markCompleted();
        } else {
            $this->release(3);
        }
    }
}
