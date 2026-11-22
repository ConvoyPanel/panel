<?php

namespace App\Jobs\Provisioner;

use App\Models\ProvisioningRun;
use App\Services\Provisioners\ProvisioningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Removes `cicustom` from a guest and has Anchor delete its snippet.
 *
 * A node that stays unreachable past the retries leaves the run uncleaned, and
 * `provisioners:sweep` dispatches this again later.
 */
class CleanUpProvisioningRunJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 5;

    public int $timeout = 60;

    public int $uniqueFor = 600;

    public function __construct(public int $runId) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 30, 120, 300];
    }

    public function uniqueId(): string
    {
        return (string) $this->runId;
    }

    public function handle(ProvisioningService $service): void
    {
        $run = ProvisioningRun::find($this->runId);

        if ($run === null) {
            return;
        }

        $service->cleanUp($run);
    }
}
