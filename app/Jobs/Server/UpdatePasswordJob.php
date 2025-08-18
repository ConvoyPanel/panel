<?php

namespace App\Jobs\Server;

use Throwable;
use App\Models\DeploymentStep;
use App\Enums\Server\DeploymentStatus;
use App\Traits\Jobs\FailsWithStep;
use App\Services\Servers\ServerAuthService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class UpdatePasswordJob implements ShouldQueue
{
    use Dispatchable, FailsWithStep, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 10;

    public function __construct(
        #[WithoutRelations]
        public DeploymentStep $step,
        public string $password,
    ) {}

    public function middleware(): array
    {
        return [
            new SkipIfBatchCancelled,
            new WithoutOverlapping(
                $this->step->deployment->server->id
            ),
        ];
    }

    public function handle(ServerAuthService $service): void
    {
        $this->step->update([
            'status' => DeploymentStatus::RUNNING,
            'started_at' => now(),
        ]);

        $service->setPassword($this->step->deployment->server, $this->password);

        $this->step->update([
            'status' => DeploymentStatus::COMPLETED,
            'completed_at' => now(),
        ]);
    }
}
