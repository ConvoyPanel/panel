<?php

namespace App\Jobs\Server;

use App\Enums\Server\DeploymentStatus;
use App\Exceptions\Repository\Proxmox\RequestException;
use App\Models\DeploymentStep;
use App\Services\Servers\ServerBuildService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

use function now;

class WaitUntilVmIsCreatedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function retryUntil(): Carbon
    {
        return now()->addMinutes(30);
    }

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled];
    }

    public function __construct(
        #[WithoutRelations]
        public DeploymentStep $step,
    ) {}

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function handle(ServerBuildService $service): void
    {
        $deployment = $this->step->deployment;
        $server = $deployment->server;

        try {
            /** @var string $upid */
            $upid = cache()->get("server:$server->id:build-upid");
            [$current, $total] = $service->getCloneProgress(
                $this->step->deployment->server->node,
                $upid
            );

            $this->step->update([
                'progress_current' => $current,
                'progress_total' => $this->step->progress_total < $total ?
                    $total : $this->step->progress_total,
            ]);
        } catch (Exception|NotFoundExceptionInterface|ContainerExceptionInterface) {
            // Fail silently
        }

        if ($service->isVmCreated($server)) {
            $this->step->update([
                'status' => DeploymentStatus::COMPLETED,
                'completed_at' => now(),
            ]);
        } else {
            $this->release(1);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->step->update([
            'status' => DeploymentStatus::FAILED,
            'completed_at' => now(),
            'error_message' => $exception?->getMessage() ?? 'Unknown error',
        ]);
    }
}
