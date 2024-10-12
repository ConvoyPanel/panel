<?php

namespace App\Services\Backups;

use Carbon\Carbon;
use Closure;
use App\Models\Backup;
use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxActivityRepository;
use App\Repositories\Proxmox\Server\ProxmoxBackupRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class BackupMonitorService
{
    public function __construct(
        private ProxmoxActivityRepository $repository,
        private ProxmoxBackupRepository   $backupRepository,
    ) {
    }

    public function checkCreationProgress(Backup $backup, string $upid, ?Closure $callback = null)
    {
        $status = $this->repository->setServer($backup->server)->getStatus($upid);
        $logs = $this->repository->setServer($backup->server)->getLog($upid);

        // get the filename of the backup (e.g. vzdump-qemu-101-2021_01_01-00_00_00.vma.zstd)
        $fileName = null;
        foreach ($logs as $log) {
            if (preg_match("/INFO: creating vzdump archive '(.+)'/s", $log['t'], $matches)) {
                $fileName = Arr::last(explode('/', $matches[1]));
            }
        }

        // if it's running we won't do anything to the eloquent backup record for now
        if (Arr::get($status, 'status') === 'running') {
            if ($callback) {
                $callback();
            }

            return;
        }

        if (Str::lower(Arr::get($status, 'exitstatus')) === 'ok') {
            $archives = $this->backupRepository->setServer($backup->server)->getBackups();
            $archive = collect($archives)->where(
                'volid',
                "{$backup->server->node->backup_storage}:backup/{$fileName}",
            )->first();

            $backup->update([
                'is_successful' => true,
                'file_name' => $fileName,
                'size' => Arr::get($archive, 'size', 0),
                'completed_at' => Carbon::now(),
            ]);
        } else {
            $backup->update([
                'is_successful' => false,
                'completed_at' => Carbon::now(),
            ]);
        }
    }

    public function checkRestorationProgress(
        Server $server,
        string $upid,
        ?Closure $callback = null,
    ) {
        $status = $this->repository->setServer($server)->getStatus($upid);

        if (Arr::get($status, 'status') === 'running') {
            if ($callback) {
                $callback();
            }

            return;
        }

        $server->update([
            'status' => null,
        ]);
    }
}
