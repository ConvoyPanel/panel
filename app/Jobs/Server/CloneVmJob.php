<?php

namespace App\Jobs\Server;

use App\Exceptions\Proxmox\RequestException;
use App\Models\DeploymentStep;
use App\Services\Servers\ServerBuildService;
use App\Traits\Jobs\FailsWithStep;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use function now;

/**
 * Owns the `clone` step end to end: it starts the Proxmox clone exactly once and
 * then polls the same task to completion, releasing itself between checks. The
 * clone's UPID is recorded on the step (via kickOnce), so a released or retried
 * run resumes polling instead of starting a second clone.
 */
class CloneVmJob implements ShouldQueue
{
    use Dispatchable, FailsWithStep, InteractsWithQueue, Queueable, SerializesModels;

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
        $deployment = $this->step->deployment;
        $server = $deployment->server;

        $this->step->kickOnce(fn () => $service->build($server, $deployment->template));

        try {
            [$current, $total] = $service->getCloneProgress($server->node, $this->step->task_upid);

            // Proxmox's reported total is authoritative and stable across polls,
            // so adopt it and clamp current to it — letting the total grow made
            // the percentage jump backwards. The step's seeded disk-size
            // estimate only gives the bar a scale before the first poll lands.
            $this->step->update([
                'progress_current' => min($current, $total),
                'progress_total' => $total,
            ]);
        } catch (Exception|NotFoundExceptionInterface|ContainerExceptionInterface) {
            // The clone task status is not always readable immediately; a failed
            // read just means we poll again rather than fail the step.
        }

        if ($service->isVmCreated($server)) {
            $this->step->markCompleted();
        } else {
            $this->release(now()->addMilliseconds(250));
        }
    }
}
