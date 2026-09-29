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
use Illuminate\Support\Str;

class ServerBuildDispatchService
{
    public function __construct(private ServerBuildService $buildService)
    {
    }

    public function build(ServerDeploymentData $deployment): void
    {
        $jobs = $this->getChainedBuildJobs($deployment);

        Bus::chain($jobs)
            ->catch(self::markInstallFailed($deployment->server->id))
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
        // A retry after a failed install can find no VM at all -- the build
        // may have died before or during the clone. Stopping and deleting a VM
        // that does not exist fails on Proxmox's "does not exist", which would
        // fail the whole chain, so go straight to building instead. If the VM
        // does exist after all, the clone refuses its VMID and nothing is lost.
        $jobs = [
            ...($this->buildService->isVmDeleted($deployment->server)
                ? []
                : $this->getChainedDeleteJobs($deployment->server)),
            ...$this->getChainedBuildJobs($deployment),
        ];

        Bus::chain($jobs)
            ->catch(self::markInstallFailed($deployment->server->id))
            ->dispatch();

        $deployment->server->update(['status' => Status::INSTALLING->value]);
    }

    /**
     * The chain's failure handler. It writes by ID rather than through the
     * model captured at dispatch: that copy is serialized with the chain, and
     * if it already read install_failed -- as it does when retrying a failed
     * install -- Eloquent sees nothing dirty and skips the write, leaving the
     * server stuck on "installing".
     */
    private static function markInstallFailed(int $serverId): \Closure
    {
        return static fn () => Server::whereKey($serverId)
            ->update(['status' => Status::INSTALL_FAILED->value]);
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

        if (Str::contains(Str::lower($deployment->template->name), 'windows')) {
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
