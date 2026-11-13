<?php

namespace App\Jobs\Server;

use App\Data\Anchor\AnchorJobData;
use App\Enums\Anchor\AnchorJobStatus;
use App\Enums\Server\DeploymentStatus;
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
 * Owns the `install-guest` step: hands the destination Anchor a URL and a
 * hash, and polls the restore it starts.
 *
 * The archive never passes through the panel. The destination pulls it
 * straight off the source node over the tailnet the two already share, with a
 * token that names one artifact on one node and expires. The panel has no
 * business carrying multi-gigabyte bodies and no way to do it well.
 *
 * A checksum mismatch is the agent's verdict, not the panel's: it hashes while
 * it downloads and refuses to restore an archive that does not match. Either
 * way this step fails and the source guest, which has not been touched, is
 * still there to go back to.
 */
class InstallMigratedGuestJob implements ShouldQueue
{
    use Dispatchable, FailsWithStep, InteractsWithQueue, Queueable, SerializesModels;

    /** The download and the `qmrestore` after it are both slow on a big disk. */
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
        $destination = $transfer->destinationNode;

        if ($transfer->artifact === null || $transfer->sha256 === null) {
            throw new RuntimeException('The export did not produce an artifact for the destination to install.');
        }

        $this->step->kickOnce(function () use ($anchor, $transfer, $destination) {
            $job = $anchor->install(
                destination: $destination,
                name: $transfer->server->name,
                // Minted here rather than at export time so its clock starts
                // when the download does. A token issued before a two-hour
                // dump would spend that time expiring.
                url: $anchor->artifactUrl($transfer->sourceNode, (string) $transfer->artifact),
                sha256: (string) $transfer->sha256,
                size: $transfer->size,
                vmid: $transfer->destination_vmid,
                storage: $transfer->destination_storage,
            );

            $transfer->forceFill(['install_job_id' => $job->id])->save();

            return $job->id;
        });

        $jobId = $this->step->task_upid;

        if ($jobId === null) {
            throw new RuntimeException('Anchor accepted the install but returned no job to follow.');
        }

        $job = $anchor->installStatus($destination, $jobId);

        $this->publishProgress($job);

        if (! $job->status->isTerminal()) {
            $this->release(now()->addSeconds(5));

            return;
        }

        if ($job->status !== AnchorJobStatus::Completed) {
            $reason = $job->reason($job->status === AnchorJobStatus::Cancelled
                ? 'the install was cancelled.'
                : 'the install failed without a reason.');

            if ($job->status === AnchorJobStatus::Failed && $this->artifactIsGone($reason)) {
                $this->exportAgain($transfer);

                return;
            }

            throw new RuntimeException(sprintf(
                'Anchor could not install this guest on %s: %s',
                $destination->name,
                $reason,
            ));
        }

        $this->step->markCompleted();
    }

    /**
     * Whether the download failed because the archive is no longer there.
     *
     * An artifact has a TTL on the source node and does not survive an Anchor
     * restart, so this is an ordinary thing to find rather than a corruption:
     * the source guest is still sitting there untouched and dumping it again
     * costs time, not correctness. Matched on the agent's message because the
     * install job reports one string for every way a fetch can fail, and a
     * 404 is the only one of them that is worth re-exporting for.
     */
    private function artifactIsGone(string $reason): bool
    {
        foreach (['404', 'not found', 'no such artifact', 'gone'] as $needle) {
            if (str_contains(strtolower($reason), $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Put an export and a fresh install back at the front of the chain.
     *
     * Not a rollback: nothing has been destroyed, the source guest is
     * untouched, and a second dump is exactly what the situation calls for.
     * Both steps are reset so their `kickOnce` guards re-arm, and the attempt
     * is counted so a node that loses every artifact stops the migration
     * instead of exporting forever.
     */
    private function exportAgain(ServerMigrationTransfer $transfer): void
    {
        $export = $this->step->deployment->steps()->where('name', 'export-guest')->first();

        if ($export === null || $transfer->export_attempts >= ServerMigrationTransfer::MAX_EXPORT_ATTEMPTS) {
            throw new RuntimeException(
                'The exported archive is no longer on the source node, and re-exporting it did not help.',
            );
        }

        $transfer->forceFill([
            'artifact' => null,
            'sha256' => null,
            'size' => null,
            'export_job_id' => null,
            'install_job_id' => null,
        ])->save();

        foreach ([$this->step, $export] as $step) {
            $step->update([
                'task_upid' => null,
                'status' => DeploymentStatus::PENDING,
                'started_at' => null,
                'completed_at' => null,
            ]);
        }

        // Prepended in reverse, because each one goes to the front: the export
        // runs, then this same install step runs again against the new
        // artifact, then the rest of the chain carries on unchanged.
        $this->prependToChain(new self($this->step, $this->transferId));
        $this->prependToChain(new ExportGuestJob($export, $this->transferId));
    }

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
