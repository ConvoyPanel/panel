<?php

namespace App\Actions\Server;

use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\ProgressMode;
use App\Enums\Server\ServerLifecycle;
use App\Jobs\Backup\BatchPurgeServerBackupsJob;
use App\Jobs\Server\DeleteVmJob;
use App\Jobs\Server\StopVmJob;
use App\Models\Deployment;
use App\Traits\Actions\ManagesDeploymentLifecycle;
use Illuminate\Bus\Batch;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;
use Throwable;

class DeleteServerAction
{
    use ManagesDeploymentLifecycle;

    public function execute(Deployment $deployment): void
    {
        $step = $deployment->addSteps([
            [
                'name' => 'delete-backups',
                'status' => DeploymentStatus::PENDING,
                'progress_mode' => ProgressMode::DETERMINATE,
                'progress_total' => $deployment->server->backups()
                    ->whereNull('error_code')
                    ->whereNotNull('completed_at')
                    ->count() * 2, // 2 jobs for each backup: purge and monitor
            ],
        ])[0];

        $jobs = Arr::flatten([
            Bus::batch(new BatchPurgeServerBackupsJob($deployment->server))
                ->before(function () use ($step) {
                    $step->markRunning();
                })
                ->progress(function (Batch $batch) use ($step) {
                    $step->update(['progress_current' => max($batch->processedJobs() - 1, 0)]);
                })
                ->then(function () use ($step) {
                    $step->markCompleted();
                })
                ->catch(function (Batch $_, Throwable $e) use ($step) {
                    $step->markFailed($e);
                }),
            $this->getJobs($deployment),
            function () use ($deployment) {
                $deployment->server->delete();
            },
        ]);

        $deployment->server->update(['lifecycle' => ServerLifecycle::DELETING]);

        Bus::chain($jobs)
            ->catch($this->onFail($deployment, ServerLifecycle::DELETION_FAILED))
            ->dispatch();

    }

    public function getJobs(Deployment $deployment): array
    {
        $steps = $deployment->addSteps([
            [
                'name' => 'stop-vm',
                'status' => DeploymentStatus::PENDING,
                'progress_mode' => ProgressMode::INDETERMINATE,
            ],
            [
                'name' => 'delete-vm',
                'status' => DeploymentStatus::PENDING,
                'progress_mode' => ProgressMode::INDETERMINATE,
            ],
        ]);

        return [
            new StopVmJob($steps[0]),
            new DeleteVmJob($steps[1]),
        ];
    }
}
