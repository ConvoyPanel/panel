<?php

namespace App\Actions\Server;

use Throwable;
use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\PowerCommand;
use App\Enums\Server\ServerStatus;
use App\Enums\Server\State;
use App\Jobs\Backup\BatchPurgeServerBackupsJob;
use App\Jobs\Server\DeleteServerJob;
use App\Jobs\Server\MonitorStateJob;
use App\Jobs\Server\SendPowerCommandJob;
use App\Jobs\Server\WaitUntilVmIsDeletedJob;
use App\Models\Deployment;
use App\Traits\Actions\ManagesDeploymentLifecycle;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;

use function array_flatten;

class DeleteServerAction
{
    use ManagesDeploymentLifecycle;

    public function execute(Deployment $deployment): void
    {
        $step = $deployment->steps()->create([
            'name' => 'delete-backups',
            'status' => DeploymentStatus::PENDING,
            'progress_total' => $deployment->server->backups()
                ->whereNull('errors')
                ->whereNotNull('completed_at')
                ->count() * 2, // 2 jobs for each backup: purge and monitor
        ]);

        $jobs = array_flatten([
            Bus::batch(new BatchPurgeServerBackupsJob($deployment->server))
                ->before(function () use ($step) {
                    $step->start();
                })
                ->progress(function (Batch $batch) use ($step) {
                    $step->update(['progress_current' => max($batch->processedJobs() - 1, 0)]);
                })
                ->then(function () use ($step) {
                    $step->complete();
                })
            ->catch(function (Batch $_, Throwable $e) use ($step) {
                $step->update([
                    'status' => DeploymentStatus::FAILED,
                    'completed_at' => now(),
                    'error_message' => $e->getMessage(),
                ]);
            }),
            $this->getJobs($deployment),
            function () use ($deployment) {
                $deployment->server->delete();
            },
        ]);

        $deployment->server->update(['status' => ServerStatus::DELETING]);

        Bus::chain($jobs)
            ->catch($this->onFail($deployment, ServerStatus::DELETION_FAILED))
            ->dispatch();

    }

    public function getJobs(Deployment $deployment): array
    {
        $steps = $deployment->steps()->createMany([
            [
                'name' => 'stop-vm',
                'status' => DeploymentStatus::PENDING,
            ],
            [
                'name' => 'delete-vm',
                'status' => DeploymentStatus::PENDING,
            ],
        ]);

        return [
            new SendPowerCommandJob($steps[0], PowerCommand::KILL),
            new MonitorStateJob($steps[0], State::STOPPED),
            new DeleteServerJob($steps[1]),
            new WaitUntilVmIsDeletedJob($steps[1]),
        ];
    }
}
