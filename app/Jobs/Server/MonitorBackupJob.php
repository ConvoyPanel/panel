<?php

namespace Convoy\Jobs\Server;

use Convoy\Models\Backup;
use Convoy\Services\Backups\BackupMonitorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Convoy\Jobs\Concerns\LocksPerSubject;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class MonitorBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LocksPerSubject;

    public int $timeout = 60;

    public function retryUntil(): Carbon
    {
        return now()->addDay();
    }

    public function __construct(protected int $backupId, protected string $upid)
    {
    }

    /**
     * Wait before retrying after an error instead of retrying in a tight loop.
     * The last delay repeats until the job gives up.
     */
    public function backoff(): array
    {
        return [3, 10, 30];
    }

    public function middleware(): array
    {
        return [$this->lockPerSubject($this->backupId)];
    }

    public function handle(BackupMonitorService $service): void
    {
        $backup = Backup::findOrFail($this->backupId);

        $service->checkCreationProgress($backup, $this->upid, fn () => $this->release(3));
    }
}
