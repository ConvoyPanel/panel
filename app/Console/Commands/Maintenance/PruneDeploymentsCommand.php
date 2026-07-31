<?php

namespace App\Console\Commands\Maintenance;

use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\ServerLifecycle;
use App\Models\Deployment;
use App\Models\DeploymentStep;
use App\Models\Server;
use Illuminate\Console\Command;

class PruneDeploymentsCommand extends Command
{
    protected $signature = 'maintenance:prune-deployments {--retention-period=} {--stuck-age=}';

    protected $description = 'Mark stuck deployments as failed and prune old deployments.';

    public function handle(): void
    {
        $this->markStuckDeploymentsAsFailed();
        $this->pruneOldDeployments();
    }

    private function markStuckDeploymentsAsFailed(): void
    {
        $stuckAge = $this->option('stuck-age') ?? config('deployments.stuck_age', 1440);

        if (! $stuckAge || ! is_numeric($stuckAge)) {
            return;
        }

        $stuckAge = (int) $stuckAge;
        $threshold = now()->subMinutes($stuckAge);

        // Measure staleness from when the deployment actually started running,
        // not when it was requested — a queue backlog should not trip the
        // timeout before the work has had a chance to begin. Older rows without
        // a started_at fall back to requested_at.
        $stuckDeploymentsQuery = Deployment::query()
            ->where('status', DeploymentStatus::RUNNING)
            ->whereRaw('COALESCE(started_at, requested_at) <= ?', [$threshold]);

        $count = $stuckDeploymentsQuery->count();

        if ($count > 0) {
            $this->info("Marking {$count} stuck deployments as failed.");

            $stuckDeploymentsQuery->chunk(100, function ($deployments) {
                $deploymentIds = $deployments->pluck('id')->toArray();
                $serverIds = $deployments->pluck('server_id')->unique()->toArray();

                // Mark running steps as failed
                DeploymentStep::query()
                    ->whereIn('deployment_id', $deploymentIds)
                    ->where('status', DeploymentStatus::RUNNING)
                    ->update([
                        'status' => DeploymentStatus::FAILED,
                        'completed_at' => now(),
                        'error_message' => 'Deployment timed out.',
                    ]);

                // Mark deployments as failed
                Deployment::query()
                    ->whereIn('id', $deploymentIds)
                    ->update([
                        'status' => DeploymentStatus::FAILED,
                        'completed_at' => now(),
                    ]);

                // Mark servers that are stuck in installing as install_failed
                Server::query()
                    ->whereIn('id', $serverIds)
                    ->where('lifecycle', ServerLifecycle::INSTALLING)
                    ->update([
                        'lifecycle' => ServerLifecycle::INSTALL_FAILED,
                    ]);
            });
        } else {
            $this->info('No stuck deployments found.');
        }
    }

    private function pruneOldDeployments(): void
    {
        $retentionPeriod = $this->option('retention-period') ?? config('deployments.retention_period', 90);

        if (! $retentionPeriod || ! is_numeric($retentionPeriod)) {
            return;
        }

        $retentionPeriod = (int) $retentionPeriod;
        $threshold = now()->subDays($retentionPeriod);

        $query = Deployment::query()
            ->where('requested_at', '<=', $threshold);

        $count = $query->count();

        if ($count > 0) {
            $this->warn("Pruning {$count} deployments older than {$retentionPeriod} days.");
            $query->delete();
        } else {
            $this->info('No old deployments to prune.');
        }
    }
}
