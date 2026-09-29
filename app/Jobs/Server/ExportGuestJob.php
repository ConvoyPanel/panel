<?php

namespace App\Jobs\Server;

use App\Data\Anchor\AnchorJobData;
use App\Enums\Anchor\AnchorJobStatus;
use App\Exceptions\Service\Anchor\AnchorRequestException;
use App\Models\DeploymentStep;
use App\Models\ServerMigrationTransfer;
use App\Services\Anchor\AnchorMigrationClient;
use App\Traits\Jobs\FailsWithStep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use RuntimeException;

use function now;

/**
 * Owns the `export-guest` step: asks the source Anchor to `vzdump` the guest
 * and polls that job until there is an artifact with a hash.
 *
 * The guest is already stopped when this runs, and stays stopped: a
 * snapshot-mode archive is crash-consistent as of the moment it began, so
 * every write the guest made during the transfer would be silently lost at
 * cutover. That is the downtime this transport buys correctness with, and it
 * is why the mode is fixed in the protocol rather than offered as an option.
 *
 * Nothing about the source guest is changed here. The archive is a copy, and
 * the guest it was taken from is what a rollback restarts.
 */
class ExportGuestJob implements ShouldQueue
{
    use Dispatchable, FailsWithStep, InteractsWithQueue, Queueable, SerializesModels;

    /** A multi-gigabyte `vzdump` of a slow local disk is measured in hours. */
    public function retryUntil(): Carbon
    {
        return now()->addHours(6);
    }

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled];
    }

    public function __construct(
        #[WithoutRelations]
        public DeploymentStep $step,
        public int $transferId,
    ) {}

    /**
     * @throws AnchorRequestException
     */
    public function handle(AnchorMigrationClient $anchor): void
    {
        $transfer = ServerMigrationTransfer::findOrFail($this->transferId);
        $source = $transfer->sourceNode;

        // The job id is the step's durable handle, exactly as a PVE UPID is
        // for the cluster transport: on every run after the first it is
        // already set, so the export is issued once however many times this
        // job is retried or released.
        $this->step->kickOnce(function () use ($anchor, $transfer, $source) {
            // Addressed to the node that holds the guest, and only ever that
            // one: the agent refuses to dump a VMID that is not in its own
            // `qemu-server` directory, so pointing this at a cluster peer
            // fails rather than quietly dumping the wrong thing.
            $job = $anchor->export($source, $transfer->source_vmid);

            $transfer->forceFill([
                'export_job_id' => $job->id,
                'export_attempts' => $transfer->export_attempts + 1,
            ])->save();

            return $job->id;
        });

        $jobId = $this->step->task_upid;

        if ($jobId === null) {
            throw new RuntimeException('Anchor accepted the export but returned no job to follow.');
        }

        $job = $anchor->exportStatus($source, $jobId);

        $this->publishProgress($job);

        if (! $job->status->isTerminal()) {
            $this->release(now()->addSeconds(5));

            return;
        }

        if ($job->status !== AnchorJobStatus::Completed) {
            throw new RuntimeException(sprintf(
                'Anchor could not export this guest from %s: %s',
                $source->name,
                $job->reason($job->status === AnchorJobStatus::Cancelled
                    ? 'the export was cancelled.'
                    : 'the export failed without a reason.'),
            ));
        }

        // An export that reports success without an artifact or a hash is not
        // a success the install side can use: the destination verifies what it
        // downloaded against this hash, and a missing one would mean restoring
        // an unverified archive. Refuse rather than transfer blind.
        if ($job->artifact === null || $job->sha256 === null) {
            throw new RuntimeException(sprintf(
                'Anchor on %s reported the export finished but named no artifact to download.',
                $source->name,
            ));
        }

        $transfer->forceFill([
            'artifact' => $job->artifact,
            'sha256' => $job->sha256,
            'size' => $job->size,
        ])->save();

        $this->step->markCompleted();
    }

    /**
     * Bytes written so far against the total the agent expects. The step's
     * progress is the operator's only view of a transfer that can run for
     * hours, so it is written on every poll rather than only at the end.
     */
    private function publishProgress(AnchorJobData $job): void
    {
        if ($job->total === null || $job->total === 0) {
            return;
        }

        $this->step->update([
            'progress_current' => $job->transferred,
            'progress_total' => $job->total,
        ]);
    }
}
