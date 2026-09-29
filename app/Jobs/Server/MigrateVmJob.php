<?php

namespace App\Jobs\Server;

use App\Enums\Activity\TaskExitStatus;
use App\Exceptions\Proxmox\RequestException;
use App\Models\DeploymentStep;
use App\Models\Node;
use App\Services\Proxmox\Server\ProxmoxActivityClient;
use App\Services\Proxmox\Server\ProxmoxMigrationClient;
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
 * Owns the `migrate-vm` step: issues the migrate exactly once and polls that
 * task to completion, releasing itself between checks.
 *
 * The task runs on the *source* node and has to be polled there. `servers`
 * still points at the source for the whole of this step; the rebind is the next
 * step's job and happens only once this one succeeds. That ordering is the
 * point: a guest that half-moved is described by a row that still says where it
 * was, and the placement reconciler will correct it on the next poll either way.
 */
class MigrateVmJob implements ShouldQueue
{
    use Dispatchable, FailsWithStep, InteractsWithQueue, Queueable, SerializesModels;

    /** Storage migration of a large local disk is slow; a shared-storage move is seconds. */
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
        public int $targetNodeId,
        public bool $online,
    ) {}

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function handle(ProxmoxMigrationClient $migration, ProxmoxActivityClient $activity): void
    {
        $server = $this->step->deployment->server;
        $target = Node::findOrFail($this->targetNodeId);
        $source = $server->node;

        $this->step->kickOnce(
            fn () => $migration->setServer($server)->migrate($target->name, $this->online),
        );

        $upid = $this->step->task_upid;

        if ($upid === null) {
            throw new RuntimeException('Proxmox accepted the migration but returned no task to follow.');
        }

        $task = $activity->setServer($server)->setNode($source)->getStatus($upid);

        if ($task->endTime === null) {
            $this->release(now()->addSecond());

            return;
        }

        // OK and WARNINGS are both successful exits; anything else is a raw
        // message from PVE, which is the most useful thing to show the operator.
        if (! $task->exitStatus instanceof TaskExitStatus) {
            throw new RuntimeException(sprintf(
                'Proxmox could not migrate this guest to %s: %s',
                $target->name,
                $task->exitStatus ?? 'the task failed without a reason.',
            ));
        }

        $this->step->markCompleted();
    }
}
