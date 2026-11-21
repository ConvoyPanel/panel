<?php

namespace App\Actions\Server;

use App\Enums\Server\ServerLifecycle;
use App\Exceptions\Proxmox\RequestException;
use App\Models\Deployment;
use App\Services\Provisioners\ProvisioningService;
use App\Traits\Actions\ManagesDeploymentLifecycle;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;

class RebuildServerAction
{
    use ManagesDeploymentLifecycle;

    public function __construct(
        private readonly BuildServerAction $buildServerAction,
        private readonly DeleteServerAction $deleteServerAction,
        private readonly ProvisioningService $provisioning,
    ) {}

    /**
     * @param  array<string, string|null>  $provisionerFields  by field key
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function execute(Deployment $deployment, ?string $accountPassword, array $provisionerFields = []): void
    {
        // The guest the old scripts were written for is about to be destroyed,
        // so they will never report. Their snippet comes off the node now
        // rather than when the timeout gives up on them.
        $this->provisioning->cancelOpenRuns($deployment->server, except: $deployment);

        $jobs = Arr::flatten([
            $this->onStart($deployment),
            $this->deleteServerAction->getJobs($deployment),
            $this->buildServerAction->getJobs($deployment, $accountPassword, $provisionerFields),
            $this->onComplete($deployment),
        ]);

        $deployment->server->update(['lifecycle' => ServerLifecycle::INSTALLING]);

        Bus::chain($jobs)
            ->catch($this->onFail($deployment))
            ->dispatch();
    }
}
