<?php

namespace App\Jobs\Server;

use App\Exceptions\Proxmox\RequestException;
use App\Jobs\Middleware\ExpiringWithoutOverlapping;
use App\Models\DeploymentStep;
use App\Services\Servers\VmSyncService;
use App\Traits\Jobs\FailsWithStep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\SerializesModels;

class ConfigureVmJob implements ShouldQueue
{
    use Dispatchable, FailsWithStep, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * A configure pass is a handful of Proxmox writes, but one of them -- the
     * primary disk's grow -- now waits for PVE's resize task and retries it once
     * if the node times out on it, which is worth up to 70s on its own. Twenty
     * seconds used to be enough and no longer is.
     *
     * Kept below {@see ExpiringWithoutOverlapping::EXPIRES_AFTER} so the order of
     * events under a genuinely stuck node is the safe one: the job is killed
     * while it still holds the server's overlap lock, rather than the lock
     * expiring out from under a job that is still writing to the guest.
     */
    public int $timeout = 110;

    public function __construct(
        #[WithoutRelations]
        public DeploymentStep $step,
    ) {}

    public function middleware(): array
    {
        return [
            new SkipIfBatchCancelled,
            new ExpiringWithoutOverlapping((string) $this->step->deployment->server_id),
        ];
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function handle(VmSyncService $service): void
    {
        $this->step->run(
            fn () => $service->handle($this->step->deployment->server),
        );
    }
}
