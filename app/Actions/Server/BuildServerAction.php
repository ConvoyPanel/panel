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
use App\Jobs\Server\PrepareProvisionersJob;
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
     * @param  array<string, string|null>  $provisionerFields  by field key
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function execute(Deployment $deployment, ?string $accountPassword, array $provisionerFields = []): void
    {
        $jobs = Arr::flatten([
            $this->onStart($deployment),
            $this->getJobs($deployment, $accountPassword, $provisionerFields),
            $this->onComplete($deployment),
        ]);

        $deployment->server->update(['lifecycle' => ServerLifecycle::INSTALLING]);

        Bus::chain($jobs)
            ->catch($this->onFail($deployment))
            ->dispatch();
    }

    /**
     * @param  array<string, string|null>  $provisionerFields  by field key
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getJobs(Deployment $deployment, ?string $accountPassword, array $provisionerFields = []): array
    {
        $installs = $deployment->type === DeploymentType::INSTALL || $deployment->type === DeploymentType::REINSTALL;

        if ($installs) {
            $jobs = $this->createInstallStepsAndJobs($deployment);
        } else {
            $jobs = $this->createConfigureStepsAndJobs($deployment);
        }

        return $this->appendOptionalJobs($deployment, $accountPassword, $jobs, $installs ? $provisionerFields : null);
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
                'progress_total' => (int) $version->size,
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

    /**
     * `$provisionerFields` is null when the deployment installs no OS, so there
     * is no first boot to run provisioner scripts on.
     *
     * @param  array<string, string|null>|null  $provisionerFields  by field key
     */
    private function appendOptionalJobs(
        Deployment $deployment,
        ?string $accountPassword,
        array $jobs,
        ?array $provisionerFields,
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

        // After everything else that writes cloud-init, and before the first
        // boot: the scripts have to be on the node when Proxmox builds the
        // guest's cloud-init drive, which it does as the guest starts.
        if ($provisionerFields !== null && $deployment->imageDefinition?->provisioners()->exists()) {
            $step = $deployment->addSteps([
                [
                    'name' => 'prepare-provisioners',
                    'status' => DeploymentStatus::PENDING,
                    'progress_mode' => ProgressMode::INDETERMINATE,
                ],
            ])[0];
            $jobs[] = new PrepareProvisionersJob($step, $provisionerFields);
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
