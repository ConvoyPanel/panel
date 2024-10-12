<?php

namespace App\Services\Servers;

use App\Models\Server;
use App\Enums\Server\Status;
use Illuminate\Support\Facades\Bus;
use App\Jobs\Server\PurgeBackupsJob;
use App\Exceptions\Http\Server\ServerStatusConflictException;

class ServerDeletionService
{
    public function __construct(private ServerBuildDispatchService $buildDispatchService)
    {
    }

    public function handle(Server $server, bool $noPurge = false)
    {
        $this->validateStatus($server);

        $server->update(['status' => Status::DELETING->value]);

        if (! $noPurge) {
            Bus::chain([
                new PurgeBackupsJob($server->id),
                ...$this->buildDispatchService->getChainedDeleteJobs($server),
                function () use ($server) {
                    Server::findOrFail($server->id)->delete();
                },
            ])
                ->catch(fn () => $server->update(['status' => Status::DELETION_FAILED->value]))
                ->dispatch();

            return;
        }

        $server->delete();
    }

    public function validateStatus(Server $server, bool $verifyStatusOnly = false)
    {
        if (
            ! is_null($server->status) && $server->status !== Status::DELETING->value
        ) {
            throw new ServerStatusConflictException($server);
        }

        if (! $verifyStatusOnly) {
            if ($server->backups()->whereNull('completed_at')->exists()) {
                throw new ServerStatusConflictException($server);
            }
        }
    }
}
