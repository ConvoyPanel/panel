<?php

namespace App\Actions\Server;

use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\ServerStatus;
use App\Exceptions\Repository\Proxmox\RequestException;
use App\Models\Deployment;
use App\Traits\Actions\ManagesDeploymentLifecycle;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Bus;

use function now;

class RebuildServerAction
{
    use ManagesDeploymentLifecycle;

    public function __construct(
        private readonly BuildServerAction $buildServerAction,
        private readonly DeleteServerAction $deleteServerAction,
    ) {
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function execute(Deployment $deployment, ?string $accountPassword): void
    {
        $jobs = [
            $this->onStart($deployment),
            ...$this->deleteServerAction->execute($deployment->server),
            ...$this->buildServerAction->getJobs($deployment, $accountPassword),
            $this->onComplete($deployment),
        ];

        $deployment->server->update(['status' => ServerStatus::INSTALLING]);

        Bus::chain($jobs)
            ->catch($this->onFail($deployment))
            ->dispatch();
    }
}
