<?php

namespace Convoy\Services\Servers;

use Convoy\Enums\Server\Status;
use Convoy\Exceptions\Http\Server\ServerStateConflictException;
use Convoy\Models\Backup;
use Convoy\Models\Server;
use Convoy\Repositories\Eloquent\BackupRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxServerRepository;
use Convoy\Services\Servers\Backups\BackupService;

class ServerDeletionService
{
    public function __construct(private BackupRepository $backupRepository, private BackupService $backupService, private ProxmoxServerRepository $serverRepository)
    {

    }

    public function handle(Server $server)
    {
        $this->validateStatus($server);

        $server->update(['status' => Status::DELETING->value]);

        $backups = $this->backupRepository->getNonFailedBackups($server)->get();

        $backups->each(function (Backup $backup) {
            $this->backupService->delete($backup);
        });

        // TODO: add snapshot deletion

        $this->serverRepository->setServer($server)->delete();
        $server->delete();

        $server->update(['status' => null]);
    }

    public function validateStatus(Server $server)
    {
        $server->validateCurrentState();

        if ($server->backups()->whereNull('completed_at')->exists()) {
            throw new ServerStateConflictException($server);
        }
    }
}