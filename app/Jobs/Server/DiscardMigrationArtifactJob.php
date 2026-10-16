<?php

namespace App\Jobs\Server;

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
use Illuminate\Support\Facades\Log;

/**
 * Owns the `discard-artifact` step: deletes the archive off the source node.
 *
 * The contract lists this last, after the source guest is destroyed. It runs
 * here instead, between the verification and the destroy, and the reason is
 * the rollback rule rather than a preference. Every failure before the source
 * guest is destroyed is recoverable by restarting a guest that was never
 * touched; a step that runs *after* the destroy has no such recovery, so
 * putting a fallible remote call there would create the one failure mode this
 * design does not have an answer for. Once the destination is verified the
 * archive is redundant, so discarding it a step early costs nothing and keeps
 * the point of no return in exactly one place.
 *
 * It is still a real step rather than a fire-and-forget: the artifact is
 * several gigabytes of a node's dump volume, and "the TTL will sweep it" is a
 * backstop, not a plan.
 *
 * It is, however, not allowed to fail the migration. A live run proved why: the
 * discard was addressed to a route the agent does not serve, the agent answered
 * 404, and a migration whose destination had already been verified was marked
 * failed -- leaving the guest present on both nodes for an operator to
 * reconcile by hand. Cleanup failing is strictly better than that: the archive
 * has a TTL and is swept whether or not anyone asks, so the worst case of
 * carrying on is a temporary file that outlives its use by a day.
 */
class DiscardMigrationArtifactJob implements ShouldQueue
{
    use Dispatchable, FailsWithStep, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

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

        $this->step->run(function () use ($anchor, $transfer) {
            if ($transfer->artifact === null) {
                return;
            }

            try {
                $anchor->discard($transfer->sourceNode, $transfer->artifact);
            } catch (AnchorRequestException $exception) {
                // Surfaced, not swallowed, and not fatal: the guest is already
                // verified on the destination, so aborting here would undo a
                // migration that worked over a file the sweeper will remove.
                Log::warning('Could not discard a migration artifact; leaving it to the sweeper.', [
                    'server_id' => $transfer->server_id,
                    'node' => $transfer->sourceNode->name,
                    'artifact' => $transfer->artifact,
                    'reason' => $exception->getMessage(),
                ]);

                return;
            }

            // Cleared so a rollback or a retry does not try to delete it
            // twice, and so the row reads as what still exists rather than as
            // what once did. Only on success: an artifact the agent still holds
            // must stay named here, or nothing can report it later.
            $transfer->forceFill(['artifact' => null])->save();
        });
    }
}
