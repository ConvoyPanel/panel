<?php

namespace App\Jobs\Server;

use App\Exceptions\Proxmox\RequestException;
use App\Models\DeploymentStep;
use App\Models\ServerMigrationTransfer;
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
use RuntimeException;

use function now;

/**
 * Owns the `destroy-source` step: removes the guest from the node it came
 * from, once the copy on the destination has been verified.
 *
 * This is the point of no return, and it is guarded twice. The chain will not
 * reach it before {@see VerifyMigratedGuestJob}, and this job refuses to run
 * against a transfer that verification did not stamp. A guard that duplicates
 * the chain's own ordering is worth having when the thing it prevents is
 * destroying the only remaining copy of somebody's server.
 *
 * It works through the `servers` row as it stands, which still names the
 * source node and the source VMID: the rebind is the next step, deliberately,
 * so a failure here leaves a row that describes where the guest actually is.
 */
class DestroySourceGuestJob implements ShouldQueue
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
        public int $transferId,
    ) {}

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function handle(ServerBuildService $service): void
    {
        $transfer = ServerMigrationTransfer::findOrFail($this->transferId);

        if (! $transfer->isVerified()) {
            throw new RuntimeException(
                'The destination guest has not been verified, so the source guest will not be destroyed.',
            );
        }

        $server = $this->step->deployment->server;

        try {
            $this->step->kickOnce(fn () => $service->delete($server));
        } catch (RequestException $e) {
            if (! $this->isNonexistentVMError($e)) {
                throw $e;
            }

            $this->logSwallowedNonexistentVM($server, 'destroy-source');

            $this->commit($transfer);

            return;
        }

        if ($service->isVmDeleted($server)) {
            $this->commit($transfer);
        } else {
            $this->release(3);
        }
    }

    /**
     * Record that the source guest is gone, which is what closes the rollback
     * window. Written here rather than by whatever comes next, because the
     * fact it records is this job's own and a later step failing must not make
     * it look untrue.
     */
    private function commit(ServerMigrationTransfer $transfer): void
    {
        $transfer->forceFill(['source_destroyed_at' => now()])->save();

        $this->step->markCompleted();
    }
}
