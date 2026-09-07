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
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

use function now;

/**
 * Puts the image's disks on the node, if they are not there already.
 *
 * This is the only expensive step in a build and the only one that usually does
 * nothing: the files are named after their own hashes, so the second server
 * built from an image finds them present and this completes immediately. On a
 * cold node it is a multi-gigabyte download, which is why it gets its own step
 * with its own progress rather than hiding inside the create.
 *
 * Completion is decided by the files actually being on the node, not by the
 * download task reporting success -- a task can succeed against a storage that
 * has since been pruned, and the create that follows would then fail with
 * something far less legible.
 */
class FetchImageJob implements ShouldQueue
{
    use Dispatchable, FailsWithStep, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * A cold Windows image is ~10 GB; a slow link needs room for that without
     * the build being declared dead half way through.
     */
    public function retryUntil(): Carbon
    {
        return now()->addHours(3);
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
    public function handle(ImageResidencyService $residency, ServerBuildService $build): void
    {
        $deployment = $this->step->deployment;
        $node = $deployment->server->node;
        $version = $deployment->imageVersion;

        if ($residency->isResident($node, $version)) {
            $this->step->markCompleted();

            return;
        }

        // Both disks start downloading together; the system disk's task is the
        // one worth watching, since the varstore beside it is a few hundred KiB.
        $this->step->kickOnce(
            fn () => Arr::first($residency->ensureResident($node, $version)) ?? '',
        );

        if (filled($this->step->task_upid)) {
            try {
                [$current, $total] = $build->getDownloadProgress($node, $this->step->task_upid);

                if ($total > 0) {
                    $this->step->update([
                        'progress_current' => min($current, $total),
                        'progress_total' => $total,
                    ]);
                }
            } catch (Exception) {
                // A log that cannot be read yet is not a failed download.
            }
        }

        $this->release(now()->addSeconds(2));
    }
}
