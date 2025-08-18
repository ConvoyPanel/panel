<?php

namespace App\Jobs\Server;

use App\Models\Backup;
use App\Services\Backups\BackupMonitorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Attributes\WithoutRelations;
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
    )
    {
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->backup->id)];
    }

    public function handle(BackupMonitorService $service): void
    {
        $service->checkCreationProgress($this->backup->id, $this->upid, fn () => $this->release(3));
    }
}
