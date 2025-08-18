<?php

namespace App\Services\Servers;

use App\Exceptions\Repository\Proxmox\RequestException;
use App\Models\Server;
use Illuminate\Http\Client\ConnectionException;

readonly class VmSyncService
{
    public function __construct(
        private AllocationService $allocationService,
        private CloudinitService $cloudinitService,
        private ServerNetworkService $networkService,
    ) {}

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function handle(Server $server, ?callable $onProgress = null): void
    {
        if ($onProgress === null) {
            $onProgress = fn () => null;
        }

        $this->allocationService->syncSettings($server);
        $onProgress();

        $this->cloudinitService->setHostname($server, $server->hostname);
        $onProgress();

        $this->networkService->syncSettings($server);
        $onProgress();
    }
}
