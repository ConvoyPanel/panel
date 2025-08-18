<?php

namespace App\Jobs\Server;

use Throwable;
use App\Enums\Server\DeploymentStatus;
use App\Exceptions\Repository\Proxmox\RequestException;
use App\Traits\Jobs\FailsWithStep;
use App\Models\DeploymentStep;
use App\Services\Servers\ServerBuildService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class BuildServerJob implements ShouldQueue
{
    use Dispatchable, FailsWithStep, InteractsWithQueue, Queueable, SerializesModels;

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
    public function handle(ServerBuildService $service): void
    {
        $deployment = $this->step->deployment;
        $server = $deployment->server;

        $this->step->start();

        $upid = $service->build($server, $deployment->template);

        cache()->put(
            "server:$server->id:build-upid",
            $upid,
            now()->addHour()
        );
    }
}
