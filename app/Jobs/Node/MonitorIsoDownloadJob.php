<?php

namespace App\Jobs\Node;

use App\Enums\Activity\TaskExitStatus;
use App\Enums\Activity\TaskStatus;
use App\Jobs\Middleware\ExpiringWithoutOverlapping;
use App\Models\ISO;
use App\Models\Node;
use App\Services\Proxmox\Server\ProxmoxActivityClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class MonitorIsoDownloadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function retryUntil(): Carbon
    {
        return now()->addDay();
    }

    public function __construct(protected int $isoId, protected string $upid) {}

    public function middleware()
    {
        return [new ExpiringWithoutOverlapping("node:iso.download#{$this->isoId}")];
    }

    public function handle(ProxmoxActivityClient $client): void
    {
        $iso = ISO::findOrFail($this->isoId);
        /** @var Node $node */
        $node = $iso->storage->nodes()->firstOrFail();

        $task = $client->setNode($node)->getStatus($this->upid);

        if ($task->status === TaskStatus::RUNNING) {
            $this->release(3);

            return;
        }

        if ($task->exitStatus === TaskExitStatus::OK) {
            $iso->update([
                'is_successful' => true,
                'completed_at' => Carbon::now(),
            ]);
        } else {
            $iso->update([
                'is_successful' => false,
                'completed_at' => Carbon::now(),
            ]);
        }
    }
}
