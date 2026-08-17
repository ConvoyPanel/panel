<?php

namespace App\Jobs\Server;

use App\Enums\Server\PowerCommand;
use App\Exceptions\Proxmox\RequestException;
use App\Jobs\Middleware\ExpiringWithoutOverlapping;
use App\Models\DeploymentStep;
use App\Services\Proxmox\Server\ProxmoxPowerClient;
use App\Traits\HandlesProxmoxErrors;
use App\Traits\Jobs\FailsWithStep;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\SerializesModels;

class SendPowerCommandJob implements ShouldQueue
{
    use Batchable, Dispatchable, FailsWithStep, HandlesProxmoxErrors, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 15;

    public function __construct(
        #[WithoutRelations]
        public DeploymentStep $step,
        public PowerCommand $power,
    ) {}

    public function middleware(): array
    {
        return [
            new SkipIfBatchCancelled,
            new ExpiringWithoutOverlapping((string) $this->step->deployment->server->id),
        ];
    }

    /**
     * @throws RequestException|ConnectionException
     */
    public function handle(ProxmoxPowerClient $client): void
    {
        $this->step->markRunning();

        try {
            $client->setServer($this->step->deployment->server)->send($this->power);
        } catch (RequestException $e) {
            // A VM that is already gone is a success for our purposes; any other
            // provider error must propagate so the step is not marked complete.
            if (! $this->isNonexistentVMError($e)) {
                throw $e;
            }
        }

        // Reached only when the command succeeded (or the VM was already gone).
        $this->step->markCompleted();
    }
}
