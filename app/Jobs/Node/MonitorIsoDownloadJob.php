<?php

namespace Convoy\Jobs\Node;

use Convoy\Models\ISO;
use Convoy\Services\Isos\IsoMonitorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Convoy\Jobs\Concerns\LocksPerSubject;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class MonitorIsoDownloadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LocksPerSubject;

    public int $timeout = 60;

    public function retryUntil(): Carbon
    {
        return now()->addDay();
    }

    public function __construct(protected int $isoId, protected string $upid)
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

    public function middleware()
    {
        return [$this->lockPerSubject($this->isoId)];
    }

    public function handle(IsoMonitorService $service): void
    {
        $iso = ISO::findOrFail($this->isoId);

        $service->checkDownloadProgress($iso, $this->upid, fn () => $this->release(3));
    }
}
