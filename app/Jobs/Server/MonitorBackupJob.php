<?php

namespace App\Jobs\Server;

use App\Enums\Activity\TaskExitStatus;
use App\Enums\Activity\TaskStatus;
use App\Models\Backup;
use App\Repositories\Proxmox\Server\ProxmoxActivityRepository;
use App\Repositories\Proxmox\Server\ProxmoxBackupRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class MonitorBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function retryUntil(): Carbon
    {
        return now()->addDay();
    }

    public function __construct(
        #[WithoutRelations]
        public Backup $backup,
        public string $upid
    )
    {
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->backup->id)];
    }

    public function handle(ProxmoxActivityRepository $repository, ProxmoxBackupRepository $backupRepository): void
    {
        $task = $repository->setServer($this->backup->server)->getStatus($this->upid);

        if ($task->status === TaskStatus::RUNNING) {
            $this->release(3);

            return;
        }

        $logs = $repository->setServer($this->backup->server)->getLogsByTask($this->upid);

        // get the filename of the backup (e.g. vzdump-qemu-101-2021_01_01-00_00_00.vma.zstd)
        $fileName = null;
        foreach ($logs as $log) {
            if (preg_match("/INFO: creating vzdump archive '(.+)'/s", $log->text, $matches)) {
                $fileName = Arr::last(explode('/', $matches[1]));
            }
        }

        if ($task->exitStatus === TaskExitStatus::OK) {
            $archives = $backupRepository->setServer($this->backup->server)->getBackups($this->backup->storage);
            $archive = collect($archives)->where(
                'volid',
                "{$this->backup->server->node->backup_storage}:backup/{$fileName}",
            )->first();

            $this->backup->update([
                'is_successful' => true,
                'file_name' => $fileName,
                'size' => Arr::get($archive, 'size', 0),
                'completed_at' => Carbon::now(),
            ]);
        } else {
            $this->backup->update([
                'is_successful' => false,
                'completed_at' => Carbon::now(),
            ]);
        }
    }
}
