<?php

namespace Convoy\Services\Servers;

use Convoy\Enums\Server\PowerAction;
use Convoy\Enums\Server\State;
use Convoy\Enums\Server\Status;
use Convoy\Exceptions\Http\Server\ServerStatusConflictException;
use Convoy\Jobs\Server\DeleteServerJob;
use Convoy\Jobs\Server\MonitorStateJob;
use Convoy\Jobs\Server\PurgeBackupsJob;
use Convoy\Jobs\Server\SendPowerCommandJob;
use Convoy\Models\Server;
use Illuminate\Support\Facades\Bus;

class ServerDeletionService
{
    public function __construct()
    {

    }

    public function handle(Server $server, bool $noPurge = false)
    {
        $this->validateStatus($server);

        $server->update(['status' => Status::DELETING->value]);

        if (!$noPurge) {
            Bus::batch([
                new SendPowerCommandJob($server->id, PowerAction::KILL),
                new MonitorStateJob($server->id, State::STOPPED),
                new PurgeBackupsJob($server->id),
                new DeleteServerJob($server->id),
            ])
                ->catch(fn() => $server->update(['status' => Status::DELETION_FAILED->value]))
                ->name('Delete server')
                ->dispatch();
        }

        $server->delete();

//        if (!$noPurge) {
//            $this->powerRepository->setServer($server)->send(PowerAction::KILL);
//
//            $backups = $this->backupRepository->getNonFailedBackups($server)->get();
//
//            $backups->each(function (Backup $backup) {
//                $this->backupDeletionService->handle($backup);
//            });
//
//            // TODO: add snapshot deletion
//
//            $this->serverRepository->setServer($server)->delete();
//        }
//
//        $server->delete();
    }

    public function validateStatus(Server $server, bool $verifyStatusOnly = false)
    {
        if (
            !is_null($server->status) && $server->status !== Status::DELETING->value
        ) {
            throw new ServerStatusConflictException($server);
        }

        if (!$verifyStatusOnly) {
            if ($server->backups()->whereNull('completed_at')->exists()) {
                throw new ServerStatusConflictException($server);
            }
        }
    }
}