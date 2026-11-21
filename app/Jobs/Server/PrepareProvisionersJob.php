<?php

namespace App\Jobs\Server;

use App\Jobs\Middleware\ExpiringWithoutOverlapping;
use App\Models\DeploymentStep;
use App\Services\Provisioners\ProvisioningService;
use App\Traits\Jobs\FailsWithStep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\SerializesModels;

/**
 * Owns the `prepare-provisioners` step: has Anchor write the image's scripts to
 * the node and points the guest's `cicustom` at them, before its first boot.
 *
 * Encrypted because it carries the field values the customer typed. They are
 * kept nowhere else in the panel.
 */
class PrepareProvisionersJob implements ShouldBeEncrypted, ShouldQueue
{
    use Dispatchable, FailsWithStep, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    /**
     * @param  array<string, string|null>  $fields  by field key
     */
    public function __construct(
        #[WithoutRelations]
        public DeploymentStep $step,
        public array $fields = [],
    ) {}

    public function middleware(): array
    {
        return [
            new SkipIfBatchCancelled,
            new ExpiringWithoutOverlapping((string) $this->step->deployment->server_id, $this->timeout),
        ];
    }

    public function handle(ProvisioningService $service): void
    {
        $this->step->run(
            fn () => $service->prepare($this->step->deployment, $this->fields),
        );
    }
}
