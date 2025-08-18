<?php

namespace App\Services\Servers;

use App\Data\Server\Proxmox\Config\DiskData;
use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\DeploymentType;
use App\Enums\Server\PowerCommand;
use App\Enums\Server\ServerStatus;
use App\Enums\Server\State;
use App\Exceptions\Repository\Proxmox\RequestException;
use App\Jobs\Server\BuildServerJob;
use App\Jobs\Server\ConfigureVmJob;
use App\Jobs\Server\DeleteServerJob;
use App\Jobs\Server\MonitorStateJob;
use App\Jobs\Server\SendPowerCommandJob;
use App\Jobs\Server\UpdatePasswordJob;
use App\Jobs\Server\WaitUntilVmIsCreatedJob;
use App\Jobs\Server\WaitUntilVmIsDeletedJob;
use App\Models\Deployment;
use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxConfigRepository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Bus;

use function array_reduce;
use function now;

class ServerBuildDispatchService
{
    public function __construct(private ProxmoxConfigRepository $repository) {}

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function build(Deployment $deployment, ?string $accountPassword): void
    {
        $jobs = [
            $this->onStart($deployment),
            ...$this->getChainedBuildJobs($deployment, $accountPassword),
            $this->onComplete($deployment),
        ];

        $deployment->server->update(['status' => ServerStatus::INSTALLING]);

        Bus::chain($jobs)
            ->catch($this->onFail($deployment))
            ->dispatch();
    }

    public function delete(Server $server): void
    {
        $jobs = $this->getChainedDeleteJobs($server);

        Bus::chain($jobs)
            ->dispatch();
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function rebuild(Deployment $deployment, ?string $accountPassword): void
    {
        $jobs = [
            $this->onStart($deployment),
            ...$this->getChainedDeleteJobs($deployment->server),
            ...$this->getChainedBuildJobs($deployment, $accountPassword),
            $this->onComplete($deployment),
        ];

        $deployment->server->update(['status' => ServerStatus::INSTALLING]);

        Bus::chain($jobs)
            ->catch($this->onFail($deployment))
            ->dispatch();
    }

    private function onStart(Deployment $deployment): callable
    {
        return fn () => $deployment->update(['status' => DeploymentStatus::RUNNING]);
    }

    private function onComplete(Deployment $deployment): callable
    {
        return function () use ($deployment) {
            $deployment->update([
                'status' => DeploymentStatus::COMPLETED,
                'completed_at' => now(),
            ]);
            $deployment->server->update(['status' => ServerStatus::READY]);
        };
    }

    private function onFail(Deployment $deployment): callable
    {
        return function () use ($deployment) {
            $deployment->update([
                'status' => DeploymentStatus::FAILED,
                'completed_at' => now(),
            ]);
            $deployment->server->update(['status' => ServerStatus::INSTALL_FAILED]);
        };
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    private function getChainedBuildJobs(Deployment $deployment, ?string $accountPassword): array
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
        Deployment $deployment, ?string $accountPassword, array $jobs,
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

    public function getChainedDeleteJobs(Server $server): array
    {
        $deployment = Deployment::create([
            'server_id' => $server->id,
            'type' => DeploymentType::DELETE,
        ]);

        $steps = $deployment->steps()->createMany([
            ['name' => 'kill'],
            ['name' => 'delete'],
        ]);

        return [
            new SendPowerCommandJob($steps[0], PowerCommand::KILL),
            new MonitorStateJob($server, State::STOPPED, $steps[0]),
            new DeleteServerJob($server->id),
            new WaitUntilVmIsDeletedJob($server->id),
        ];
    }
}
