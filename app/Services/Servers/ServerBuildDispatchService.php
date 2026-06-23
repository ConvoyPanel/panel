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
use Convoy\Jobs\Server\SendServerCredentialsEmailJob;
use Convoy\Jobs\Server\SyncBuildJob;
use Convoy\Jobs\Server\UpdatePasswordJob;
use Convoy\Jobs\Server\WaitUntilVmIsCreatedJob;
use Convoy\Jobs\Server\WaitUntilVmIsDeletedJob;
use Convoy\Models\Server;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

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
        // Base jobs: either create a new server or sync an existing one
        $jobs = $deployment->should_create_server
            ? [
                new BuildServerJob($deployment->server->id, $deployment->template->id),
                new WaitUntilVmIsCreatedJob($deployment->server->id),
                new SyncBuildJob($deployment->server->id),
            ]
            : [
                new SyncBuildJob($deployment->server->id),
            ];

        $isWindows = $this->isWindowsTemplate($deployment);

        if ($isWindows) {
            $jobs = [
                ...$jobs,
                new SendPowerCommandJob($deployment->server->id, PowerAction::START),
                new MonitorStateJob($deployment->server->id, State::RUNNING),
            ];

            if (! empty($deployment->account_password)) {
                $jobs[] = new UpdatePasswordJob($deployment->server->id, $deployment->account_password);
            }
        } else {
            // For non-Windows, update password first if provided
            if (! empty($deployment->account_password)) {
                $jobs[] = new UpdatePasswordJob($deployment->server->id, $deployment->account_password);
            }
            // Then power on if user wants to start on completion
            if ($deployment->start_on_completion) {
                $jobs[] = new SendPowerCommandJob($deployment->server->id, PowerAction::START);
            }
        }

        // Final callback to clear the status
        $jobs[] = function () use ($deployment) {
            Server::findOrFail($deployment->server->id)->update(['status' => null]);
        };

        if (
            config('convoy.credentials_mail.servers.enabled') &&
            ! empty($deployment->account_password)
        ) {
            $jobs[] = new SendServerCredentialsEmailJob(
                $deployment->server->id,
                $isWindows ? 'Administrator' : 'root',
                $deployment->account_password,
            );
        }

        return $jobs;
    }

    private function isWindowsTemplate(ServerDeploymentData $deployment): bool
    {
        return Str::contains(Str::lower($deployment->template?->name ?? ''), 'windows');
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
