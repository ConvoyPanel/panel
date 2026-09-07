<?php

namespace App\Jobs\Server;

use App\Models\DeploymentStep;
use App\Traits\Jobs\FailsWithStep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

/**
 * Retired. Kept for one release so queued clones do not strand.
 *
 * Servers are built by importing a disk image now; {@see FetchImageJob} and
 * {@see ImportVmJob} replaced this. Nothing dispatches it.
 *
 * It cannot forward to them. A payload queued by the previous release names a
 * deployment whose template row no longer exists -- there is no image to import
 * and no profile to import it with -- so the honest outcome is to fail the step
 * with a sentence an operator can act on. Deleting the class instead would fail
 * the same payload with `Class not found`, which says nothing and buries the
 * cause in a stack trace.
 *
 * Delete in the release after next, once no queue can still hold one.
 */
class CloneVmJob implements ShouldQueue
{
    use Dispatchable, FailsWithStep, InteractsWithQueue, Queueable, SerializesModels;

    /** No retries: the failure is structural, and repeating it only delays the message. */
    public int $tries = 1;

    public function __construct(
        #[WithoutRelations]
        public DeploymentStep $step,
    ) {}

    public function handle(): never
    {
        throw new RuntimeException(
            'This install was queued before Convoy moved to disk images and cannot be resumed. Reinstall the server to build it from an image.',
        );
    }
}
