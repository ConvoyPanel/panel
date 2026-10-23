<?php

namespace Convoy\Jobs\Server;

use Convoy\Models\Server;
use Convoy\Services\Backups\BackupMonitorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Convoy\Jobs\Concerns\LocksPerSubject;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class MonitorBackupRestorationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LocksPerSubject;

    public int $timeout = 60;

    public function retryUntil(): Carbon
    {
        return now()->addDay();
    }

    public function __construct(protected int $serverId, protected string $upid)
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
        return [$this->lockPerSubject($this->serverId)];
    }

    public function handle(BackupMonitorService $service): void
    {
        $server = Server::findOrFail($this->serverId);

        $service->checkRestorationProgress($server, $this->upid, fn () => $this->release(3));
    }
}
