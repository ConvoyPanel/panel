<?php

namespace App\Jobs\Server;

use App\Enums\Activity\TaskStatus;
use App\Models\Server;
use App\Services\Proxmox\Server\ProxmoxActivityClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Attributes\WithoutRelations;
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

    public function __construct(
        #[WithoutRelations]
        public Server $server,
        public string $upid
    )
    {
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping((string) $this->server->id)];
    }

    public function handle(ProxmoxActivityClient $client): void
    {
        $task = $client->setServer($this->server)->getStatus($this->upid);

        if ($task->status === TaskStatus::RUNNING) {
            $this->release(3);

            return;
        }

        $this->server->update([
            'status' => null,
        ]);
    }
}
