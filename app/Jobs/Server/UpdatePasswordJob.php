<?php

namespace App\Jobs\Server;

use App\Jobs\Middleware\ExpiringWithoutOverlapping;
use App\Models\DeploymentStep;
use App\Services\Servers\ServerAuthService;
use App\Traits\Jobs\FailsWithStep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
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
            new ExpiringWithoutOverlapping(
                (string) $this->step->deployment->server->id
            ),
        ];
    }

    public function handle(ServerAuthService $service): void
    {
        $this->step->run(
            fn () => $service->setPassword($this->step->deployment->server, $this->password),
        );
    }
}
