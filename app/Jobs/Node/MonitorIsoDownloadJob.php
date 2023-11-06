<?php

namespace Convoy\Jobs\Node;

use Convoy\Models\ISO;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Convoy\Services\Nodes\Isos\IsoMonitorService;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class MonitorIsoDownloadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function retryUntil()
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

    public function handle(IsoMonitorService $service): void
    {
        $iso = ISO::findOrFail($this->isoId);

        $service->checkDownloadProgress($iso, $this->upid, fn () => $this->release(3));
    }
}
