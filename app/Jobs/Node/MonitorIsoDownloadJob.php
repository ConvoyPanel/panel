<?php

namespace App\Jobs\Node;

use App\Enums\Activity\TaskExitStatus;
use App\Enums\Activity\TaskStatus;
use App\Models\ISO;
use App\Repositories\Proxmox\Server\ProxmoxActivityRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class MonitorIsoDownloadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function retryUntil(): Carbon
    {
        return now()->addDay();
    }

    public function __construct(protected int $isoId, protected string $upid)
    {
    }

    public function middleware()
    {
        return [new WithoutOverlapping("node:iso.download#{$this->isoId}")];
    }

    public function handle(ProxmoxActivityRepository $repository): void
    {
        $iso = ISO::findOrFail($this->isoId);

        $task = $repository->setNode($iso->node)->getStatus($this->upid);

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
