<?php

namespace App\Jobs\Server;

use App\Enums\Server\PowerCommand;
use App\Enums\Server\State;
use App\Exceptions\Proxmox\RequestException;
use App\Models\DeploymentStep;
use App\Services\Proxmox\Server\ProxmoxPowerClient;
use App\Services\Proxmox\Server\ProxmoxServerClient;
use App\Traits\HandlesProxmoxErrors;
use App\Traits\Jobs\FailsWithStep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

use function now;

/**
 * Owns the `stop-vm` step: it issues a single KILL exactly once and then polls
 * the guest until it reports STOPPED. A VM that is already gone counts as
 * stopped, so a nonexistent-VM error at either point completes the step.
 */
class StopVmJob implements ShouldQueue
{
    use Dispatchable, FailsWithStep, HandlesProxmoxErrors, InteractsWithQueue, Queueable, SerializesModels;

    public function retryUntil(): Carbon
    {
        return now()->addMinutes(2);
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
    public function handle(ProxmoxPowerClient $power, ProxmoxServerClient $client): void
    {
        $server = $this->step->deployment->server;

        try {
            $this->step->kickOnce(fn () => $power->setServer($server)->send(PowerCommand::KILL));

            $state = $client->setServer($server)->getState();
        } catch (RequestException $e) {
            if (! $this->isNonexistentVMError($e)) {
                throw $e;
            }

            // Already gone is already stopped.
            $this->step->markCompleted();

            return;
        }

        if ($state->state === State::STOPPED) {
            $this->step->markCompleted();
        } else {
            $this->release(1);
        }
    }
}
