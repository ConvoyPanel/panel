<?php

namespace App\Jobs\Server;

use Throwable;
use App\Enums\Server\DeploymentStatus;
use Illuminate\Http\Client\ConnectionException;
use App\Exceptions\Repository\Proxmox\RequestException;
use App\Models\DeploymentStep;
use App\Services\Servers\VmSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class ConfigureVmJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 20;

    public function __construct(
        #[WithoutRelations]
        public DeploymentStep $step,
    ) {}

    public function middleware(): array
    {
        return [
            new SkipIfBatchCancelled,
            new WithoutOverlapping($this->step->deployment->server_id),
        ];
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function handle(VmSyncService $service): void
    {
        $this->step
            ->update([
                'status' => DeploymentStatus::RUNNING,
                'started_at' => now(),
            ]);

        $service->handle($this->step->deployment->server, function () {
            $this->step->increment('progress_current');
        });

        $this->step->update([
            'status' => DeploymentStatus::COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $this->step
            ->update([
                'status' => DeploymentStatus::FAILED,
                'completed_at' => now(),
                'error_message' => $exception?->getMessage() ?? 'Unknown error',
            ]);
    }
}
