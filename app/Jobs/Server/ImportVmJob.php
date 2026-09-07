<?php

namespace App\Jobs\Server;

use App\Exceptions\Proxmox\RequestException;
use App\Models\DeploymentStep;
use App\Services\Images\ImageResidencyService;
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
 * Owns the `import` step end to end: it starts the create exactly once and then
 * polls the same task to completion, releasing itself between checks. The
 * task's UPID is recorded on the step (via kickOnce), so a released or retried
 * run resumes polling instead of building a second guest.
 *
 * By the time this runs the disks are already on the node, so the work here is
 * a local copy into the server's storage rather than a transfer -- import
 * copies every time, and there are no linked clones on this path.
 */
class ImportVmJob implements ShouldQueue
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
     * Resolved out of the container rather than injected, so the job stays
     * serialisable: only the step is queued.
     */
    private ImageResidencyService $residency;

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function handle(ServerBuildService $service, ImageResidencyService $residency): void
    {
        $this->residency = $residency;

        $deployment = $this->step->deployment;
        $server = $deployment->server;
        $version = $deployment->imageVersion;

        $this->step->kickOnce(fn () => $service->build(
            $server,
            $version,
            $this->residency->volids($server->node, $version),
        ));

        try {
            [$current, $total] = $service->getImportProgress($server->node, $this->step->task_upid);

            // Proxmox's reported total is authoritative and stable across polls,
            // so adopt it and clamp current to it — letting the total grow made
            // the percentage jump backwards. The step's seeded size, taken from
            // the image version rather than from a node, only gives the bar a
            // scale before the first poll lands.
            $this->step->update([
                'progress_current' => min($current, $total),
                'progress_total' => $total,
            ]);
        } catch (Exception|NotFoundExceptionInterface|ContainerExceptionInterface) {
            // The import task status is not always readable immediately; a
            // failed read just means we poll again rather than fail the step.
        }

        if ($service->isVmCreated($server)) {
            $this->step->markCompleted();
        } else {
            $this->release(now()->addMilliseconds(250));
        }
    }
}
