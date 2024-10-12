<?php

namespace Convoy\Services\Servers;

use Convoy\Data\Server\Deployments\ServerDeploymentData;
use Convoy\Enums\Server\PowerAction;
use Convoy\Enums\Server\State;
use Convoy\Enums\Server\Status;
use Convoy\Jobs\Server\BuildServerJob;
use Convoy\Jobs\Server\DeleteServerJob;
use Convoy\Jobs\Server\MonitorStateJob;
use Convoy\Jobs\Server\SendPowerCommandJob;
use Convoy\Jobs\Server\SyncBuildJob;
use Convoy\Jobs\Server\UpdatePasswordJob;
use Convoy\Jobs\Server\WaitUntilVmIsCreatedJob;
use Convoy\Jobs\Server\WaitUntilVmIsDeletedJob;
use Convoy\Models\Server;
use Illuminate\Support\Facades\Bus;

class ServerBuildDispatchService
{
    public function build(ServerDeploymentData $deployment): void
    {
        $jobs = $this->getChainedBuildJobs($deployment);

        Bus::chain($jobs)
            ->catch(fn () => $deployment->server->update(['status' => Status::INSTALL_FAILED->value]))
            ->dispatch();

        $deployment->server->update(['status' => Status::INSTALLING->value]);
    }

    /* the delete virtual machine method is typically not used by itself and is accompanied by other logic like server reinstallations, server deletions */
    public function delete(Server $server): void
    {
        $jobs = $this->getChainedDeleteJobs($server);

        Bus::chain($jobs)
            ->dispatch();
    }

    public function rebuild(ServerDeploymentData $deployment): void
    {
        $jobs = [
            ...$this->getChainedDeleteJobs($deployment->server),
            ...$this->getChainedBuildJobs($deployment),
        ];

        Bus::chain($jobs)
            ->catch(fn () => $deployment->server->update(['status' => Status::INSTALL_FAILED->value]))
            ->dispatch();

        $deployment->server->update(['status' => Status::INSTALLING->value]);
    }

    private function getChainedBuildJobs(ServerDeploymentData $deployment): array
    {
        if ($deployment->should_create_server) {
            $jobs = [
                new BuildServerJob($deployment->server->id, $deployment->template->id),
                new WaitUntilVmIsCreatedJob($deployment->server->id),
                new SyncBuildJob($deployment->server->id),
            ];
        } else {
            $jobs = [
                new SyncBuildJob($deployment->server->id),
            ];
        }

        if (! empty($deployment->account_password)) {
            $jobs[] = new UpdatePasswordJob($deployment->server->id, $deployment->account_password);
        }

        if ($deployment->start_on_completion) {
            $jobs[] = new SendPowerCommandJob($deployment->server->id, PowerAction::START);
        }

        $jobs[] = function () use ($deployment) {
            Server::findOrFail($deployment->server->id)->update(['status' => null]);
        };

        return $jobs;
    }

    public function getChainedDeleteJobs(Server $server): array
    {
        return [
            new SendPowerCommandJob($server->id, PowerAction::KILL),
            new MonitorStateJob($server->id, State::STOPPED),
            new DeleteServerJob($server->id),
            new WaitUntilVmIsDeletedJob($server->id),
        ];
    }
}
