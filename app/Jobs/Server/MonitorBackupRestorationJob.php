<?php

namespace App\Jobs\Server;

use App\Models\Server;
use App\Services\Backups\BackupMonitorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class MonitorBackupRestorationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function retryUntil(): Carbon
    {
        return now()->addDay();
    }

    public function __construct(protected int $serverId, protected string $upid)
    {
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping("server:backup.restore#{$this->serverId}")];
    }

    public function handle(BackupMonitorService $service): void
    {
        $server = Server::findOrFail($this->serverId);

        $service->checkRestorationProgress($server, $this->upid, fn () => $this->release(3));
    }
}
