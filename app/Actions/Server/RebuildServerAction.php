<?php

namespace App\Actions\Server;

use App\Enums\Server\ServerLifecycle;
use App\Exceptions\Proxmox\RequestException;
use App\Models\Deployment;
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
    ) {}

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function execute(Deployment $deployment, ?string $accountPassword): void
    {
        $jobs = Arr::flatten([
            $this->onStart($deployment),
            $this->deleteServerAction->getJobs($deployment),
            $this->buildServerAction->getJobs($deployment, $accountPassword),
            $this->onComplete($deployment),
        ]);

        $deployment->server->update(['lifecycle' => ServerLifecycle::INSTALLING]);

        Bus::chain($jobs)
            ->catch($this->onFail($deployment))
            ->dispatch();
    }
}
