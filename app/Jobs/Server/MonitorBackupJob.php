<?php

namespace App\Jobs\Server;

use App\Models\Backup;
use App\Services\Backups\BackupMonitorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
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

    public function __construct(protected int $backupId, protected string $upid)
    {
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping("server:backup.create#{$this->backupId}")];
    }

    public function handle(BackupMonitorService $service): void
    {
        $backup = Backup::findOrFail($this->backupId);

        $service->checkCreationProgress($backup, $this->upid, fn () => $this->release(3));
    }
}
