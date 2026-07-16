<?php

namespace App\Jobs\Server;

use App\Data\Server\Proxmox\Backup\BackupData;
use App\Enums\Activity\TaskExitStatus;
use App\Enums\Activity\TaskStatus;
use App\Enums\Server\Backup\BackupErrorCode;
use App\Models\Backup;
use App\Services\Proxmox\Server\ProxmoxActivityClient;
use App\Services\Proxmox\Server\ProxmoxBackupClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
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
    ) {}

    public function middleware(): array
    {
        return [new WithoutOverlapping((string) $this->backup->id)];
    }

    public function handle(ProxmoxActivityClient $client, ProxmoxBackupClient $backupClient): void
    {
        $task = $client->setServer($this->backup->server)->getStatus($this->upid);

        if ($task->status === TaskStatus::RUNNING) {
            $this->release(3);

            return;
        }

        $logs = $client->setServer($this->backup->server)->getLogsByTask($this->upid);

        // get the filename of the backup (e.g. vzdump-qemu-101-2021_01_01-00_00_00.vma.zstd)
        $fileName = null;
        foreach ($logs as $log) {
            if (preg_match("/INFO: creating vzdump archive '(.+)'/s", $log->text, $matches)) {
                $fileName = basename($matches[1]);
            }
        }

        if ($task->exitStatus === TaskExitStatus::OK) {
            $archives = $backupClient->setServer($this->backup->server)->getBackups($this->backup->storage);
            $archive = collect($archives)->firstWhere(
                'volumeId',
                "{$this->backup->storage->name}:backup/{$fileName}",
            );
            $archiveSize = $archive instanceof BackupData ? $archive->size : 0;

            $this->backup->update([
                'file_name' => $fileName,
                'size' => $archiveSize,
                'completed_at' => Carbon::now(),
            ]);
        } else {
            $errorMessage = $this->extractErrorMessage($logs) ?? $task->exitStatus->value;

            $this->backup->update([
                'error_code' => BackupErrorCode::classify($errorMessage),
                'error_message' => $errorMessage,
                'completed_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Pull the first `ERROR:` line out of the vzdump task log, which carries the
     * human-readable failure reason (e.g. "no space left on device").
     *
     * @param  iterable<object{text: string}>  $logs
     */
    private function extractErrorMessage(iterable $logs): ?string
    {
        foreach ($logs as $log) {
            if (preg_match('/ERROR:\s*(.+)/', $log->text, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }
}
