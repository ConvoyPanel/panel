<?php

namespace Convoy\Jobs\Server;

use Convoy\Models\Server;
use Convoy\Services\Servers\Backups\BackupMonitorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class MonitorBackupRestorationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function retryUntil()
    {
        return now()->addDay();
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $serverId, protected string $upid)
    {
        //
    }

    public function middleware()
    {
        return [new WithoutOverlapping("server:backup.restore-{$this->serverId}")];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(BackupMonitorService $service): void
    {
        $server = Server::findOrFail($this->serverId);

        $service->checkRestorationProgress($server, $this->upid, fn () => $this->release(3));
    }
}
