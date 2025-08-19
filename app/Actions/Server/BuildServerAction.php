<?php

namespace App\Actions\Server;

use App\Data\Server\Proxmox\Config\DiskData;
use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\DeploymentType;
use App\Enums\Server\PowerCommand;
use App\Enums\Server\ServerStatus;
use App\Exceptions\Repository\Proxmox\RequestException;
use App\Jobs\Server\BuildServerJob;
use App\Jobs\Server\ConfigureVmJob;
use App\Jobs\Server\SendPowerCommandJob;
use App\Jobs\Server\UpdatePasswordJob;
use App\Jobs\Server\WaitUntilVmIsCreatedJob;
use App\Models\Deployment;
use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxConfigRepository;
use App\Traits\Actions\ManagesDeploymentLifecycle;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Bus;

use function array_reduce;

class BuildServerAction
{
    use ManagesDeploymentLifecycle;

    public function __construct(private ProxmoxConfigRepository $repository) {}

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function execute(Deployment $deployment, ?string $accountPassword): void
    {
        $jobs = array_flatten([
            $this->onStart($deployment),
            $this->getJobs($deployment, $accountPassword),
            $this->onComplete($deployment),
        ]);

        $deployment->server->update(['status' => ServerStatus::INSTALLING]);

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
        if ($deployment->type === DeploymentType::INSTALL) {
            $jobs = $this->createInstallStepsAndJobs($deployment);
        } else {
            $jobs = $this->createConfigureStepsAndJobs($deployment);
        }

        return $this->appendOptionalJobs($deployment, $accountPassword, $jobs);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    private function createInstallStepsAndJobs(Deployment $deployment): array
    {
        /* This code determines the size of the template */
        $server = $deployment->server;
        $template = new Server([
            'node_id' => $server->node_id,
            'vmid' => $deployment->template->vmid,
        ]);
        $configRepository = $this->repository->setServer($template);
        $templateConfig = $configRepository->getConfig();
        $totalSize = array_reduce(
            $templateConfig->disks->all(), function (int $carry, DiskData $disk) {
                return $carry + $disk->size;
            }, 0,
        );

        $steps = $deployment->steps()->createMany([
            [
                'name' => 'clone',
                'status' => DeploymentStatus::PENDING,
                'progress_total' => $totalSize,
            ],
            [
                'name' => 'configure',
                'status' => DeploymentStatus::PENDING,
                'progress_total' => 3,
            ],
        ]);

        return [
            new BuildServerJob($steps[0]),
            new WaitUntilVmIsCreatedJob($steps[0]),
            new ConfigureVmJob($steps[1]),
        ];
    }

    private function createConfigureStepsAndJobs(Deployment $deployment): array
    {
        $step = $deployment->steps()->create([
            'name' => 'configure',
            'status' => DeploymentStatus::PENDING,
            'progress_total' => 99,
        ]);

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
            $step = $deployment->steps()->create([
                'name' => 'update-password',
                'status' => DeploymentStatus::PENDING,
            ]);
            $jobs[] = new UpdatePasswordJob($step, $accountPassword);
        }

        if ($deployment->start_on_completion) {
            $step = $deployment->steps()->create([
                'name' => 'start',
                'status' => DeploymentStatus::PENDING,
            ]);
            $jobs[] = new SendPowerCommandJob($step, PowerCommand::START);
        }

        return $jobs;
    }
}
