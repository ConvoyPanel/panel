<?php

namespace App\Jobs\Server;

use App\Enums\Server\PowerCommand;
use App\Exceptions\Repository\Proxmox\RequestException;
use App\Models\DeploymentStep;
use App\Repositories\Proxmox\Server\ProxmoxPowerRepository;
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
use Illuminate\Queue\Middleware\WithoutOverlapping;
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
        public bool $markComplete = false,
    ) {}

    public function middleware(): array
    {
        return [
            new SkipIfBatchCancelled,
            new WithoutOverlapping((string) $this->step->deployment->server->id),
        ];
    }

    /**
     * @throws RequestException|ConnectionException
     */
    public function handle(ProxmoxPowerRepository $repository): void
    {
        $this->step->start();

        try {
            $repository->setServer($this->step->deployment->server)->send($this->power);
        } catch (RequestException $e) {
            if ($this->isNonexistentVMError($e)) {
                return;
            }

            throw $e;
        } finally {
            if ($this->markComplete) {
                $this->step->complete();
            }
        }
    }
}
