<?php

namespace Convoy\Services\Servers;

use Convoy\Models\Server;
use Convoy\Enums\Server\Status;
use Illuminate\Support\Facades\Bus;
use Convoy\Jobs\Server\PurgeBackupsJob;
use Convoy\Exceptions\Http\Server\ServerStatusConflictException;

class ServerDeletionService
{
    /**
     * States in which a job chain is still working on the server. Deleting
     * then would race it; disconnecting is still allowed, as the way out when
     * one of those chains has died and left the server stuck.
     */
    private const BUSY_STATES = [
        Status::INSTALLING,
        Status::RESTORING_BACKUP,
        Status::RESTORING_SNAPSHOT,
        Status::DELETING,
    ];

    public function __construct(
        private ServerBuildDispatchService $buildDispatchService,
        private ServerBuildService         $buildService,
    )
    {
    }

    /**
     * @param bool $disconnect remove the server from Convoy only, leaving the VM
     *                         and its backups on the node untouched
     */
    public function handle(Server $server, bool $disconnect = false): void
    {
        if ($disconnect) {
            $server->delete();

            return;
        }

        $this->validateStatus($server);

        // Checked now rather than in the chain so the admin hears straight away
        // when the node can't be asked: vmExists() throws on anything other than
        // Proxmox's "does not exist", and the server is left as it was.
        $vmExists = $this->buildService->vmExists($server);

        $server->update(['status' => Status::DELETING->value]);

        $serverId = $server->id;

        Bus::chain([
            // Force: locked backups belong to the server being deleted too.
            new PurgeBackupsJob($serverId, force: true),
            // Already gone from the node -- a failed install, or deleted by hand
            // in Proxmox -- so only the Convoy entry is left to remove.
            ...($vmExists ? $this->buildDispatchService->getChainedDeleteJobs($server) : []),
            static function () use ($serverId) {
                Server::whereKey($serverId)->first()?->delete();
            },
        ])
            // By ID, not the captured model: see ServerBuildDispatchService.
            ->catch(static fn () => Server::whereKey($serverId)
                ->update(['status' => Status::DELETION_FAILED->value]))
            ->dispatch();
    }

    /**
     * A delete can start from any state except one where a chain is still
     * running -- including deletion_failed, so a failed delete can be retried --
     * and not while a backup is being taken, since Proxmox holds a lock on the
     * VM for the backup and would refuse to destroy it.
     *
     * @throws ServerStatusConflictException
     */
    public function validateStatus(Server $server): void
    {
        if (in_array($server->status, array_map(fn (Status $s) => $s->value, self::BUSY_STATES), true)) {
            throw new ServerStatusConflictException($server);
        }

        if ($server->backups()->whereNull('completed_at')->exists()) {
            throw new ServerStatusConflictException($server);
        }
    }
}
