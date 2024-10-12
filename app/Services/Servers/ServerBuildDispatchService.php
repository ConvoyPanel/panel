<?php

namespace App\Services\Servers;

use App\Models\Server;
use App\Enums\Server\State;
use App\Enums\Server\Status;
use Illuminate\Support\Facades\Bus;
use App\Enums\Server\PowerAction;
use App\Jobs\Server\SyncBuildJob;
use App\Jobs\Server\BuildServerJob;
use App\Jobs\Server\DeleteServerJob;
use App\Jobs\Server\MonitorStateJob;
use App\Jobs\Server\UpdatePasswordJob;
use App\Jobs\Server\SendPowerCommandJob;
use App\Jobs\Server\WaitUntilVmIsCreatedJob;
use App\Jobs\Server\WaitUntilVmIsDeletedJob;
use App\Data\Server\Deployments\ServerDeploymentData;

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
