<?php

namespace App\Jobs\Node;

use App\Models\ISO;
use App\Services\Isos\IsoMonitorService;
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

    public function handle(IsoMonitorService $service): void
    {
        $iso = ISO::findOrFail($this->isoId);

        $service->checkDownloadProgress($iso, $this->upid, fn () => $this->release(3));
    }
}
