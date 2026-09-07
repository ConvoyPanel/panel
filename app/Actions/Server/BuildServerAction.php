<?php

namespace App\Actions\Server;

use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\DeploymentType;
use App\Enums\Server\PowerCommand;
use App\Enums\Server\ProgressMode;
use App\Enums\Server\ServerLifecycle;
use App\Exceptions\Proxmox\RequestException;
use App\Jobs\Server\ConfigureVmJob;
use App\Jobs\Server\FetchImageJob;
use App\Jobs\Server\ImportVmJob;
use App\Jobs\Server\SendPowerCommandJob;
use App\Jobs\Server\UpdatePasswordJob;
use App\Models\Deployment;
use App\Traits\Actions\ManagesDeploymentLifecycle;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;

class BuildServerAction
{
    use ManagesDeploymentLifecycle;

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function execute(Deployment $deployment, ?string $accountPassword): void
    {
        $jobs = Arr::flatten([
            $this->onStart($deployment),
            $this->getJobs($deployment, $accountPassword),
            $this->onComplete($deployment),
        ]);

        $deployment->server->update(['lifecycle' => ServerLifecycle::INSTALLING]);

        Bus::chain($jobs)
            ->catch($this->onFail($deployment))
            ->dispatch();
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getJobs(Deployment $deployment, ?string $accountPassword): array
    {
        if ($deployment->type === DeploymentType::INSTALL || $deployment->type === DeploymentType::REINSTALL) {
            $jobs = $this->createInstallStepsAndJobs($deployment);
        } else {
            $jobs = $this->createConfigureStepsAndJobs($deployment);
        }

        return $this->appendOptionalJobs($deployment, $accountPassword, $jobs);
    }

    /**
     * Both sizes come out of the panel's own records.
     *
     * This used to open a connection to the node and read the template's config
     * just to size a progress bar. There is no template to read any more, and
     * the image version already knows both figures -- what has to be
     * transferred, and what the imported disk will occupy -- so a build no
     * longer needs the node to be reachable before it can be queued.
     */
    private function createInstallStepsAndJobs(Deployment $deployment): array
    {
        $version = $deployment->imageVersion;

        $steps = $deployment->addSteps([
            [
                'name' => 'fetch-image',
                'status' => DeploymentStatus::PENDING,
                'progress_mode' => ProgressMode::DETERMINATE,
                'progress_total' => (int) $version->size_bytes,
            ],
            [
                'name' => 'import',
                'status' => DeploymentStatus::PENDING,
                'progress_mode' => ProgressMode::DETERMINATE,
                'progress_total' => $version->minimumDiskSize(),
            ],
            [
                'name' => 'configure',
                'status' => DeploymentStatus::PENDING,
                'progress_mode' => ProgressMode::INDETERMINATE,
            ],
        ]);

        return [
            new FetchImageJob($steps[0]),
            new ImportVmJob($steps[1]),
            new ConfigureVmJob($steps[2]),
        ];
    }

    private function createConfigureStepsAndJobs(Deployment $deployment): array
    {
        $step = $deployment->addSteps([
            [
                'name' => 'configure',
                'status' => DeploymentStatus::PENDING,
                'progress_mode' => ProgressMode::INDETERMINATE,
            ],
        ])[0];

        return [
            new ConfigureVmJob($step),
        ];
    }

    private function appendOptionalJobs(
        Deployment $deployment,
        ?string $accountPassword,
        array $jobs,
    ): array {
        if (filled($accountPassword)) {
            $step = $deployment->addSteps([
                [
                    'name' => 'update-password',
                    'status' => DeploymentStatus::PENDING,
                    'progress_mode' => ProgressMode::INDETERMINATE,
                ],
            ])[0];
            $jobs[] = new UpdatePasswordJob($step, $accountPassword);
        }

        if ($deployment->start_on_completion) {
            $step = $deployment->addSteps([
                [
                    'name' => 'start-vm',
                    'status' => DeploymentStatus::PENDING,
                    'progress_mode' => ProgressMode::INDETERMINATE,
                ],
            ])[0];
            $jobs[] = new SendPowerCommandJob($step, PowerCommand::START);
        }

        return $jobs;
    }
}
