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

            $anchor->discard($transfer->sourceNode, $transfer->artifact);

            // Cleared so a rollback or a retry does not try to delete it
            // twice, and so the row reads as what still exists rather than as
            // what once did.
            $transfer->forceFill(['artifact' => null])->save();
        });
    }
}
