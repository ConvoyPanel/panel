<?php

namespace App\Services\Nodes;

use App\Exceptions\Http\Server\ConfigModifiedException;
use App\Exceptions\Proxmox\RequestException;
use App\Jobs\Server\SyncServerRateLimitJob;
use App\Models\Server;
use App\Services\Servers\OveragePenaltyResolver;
use App\Services\Servers\ServerNetworkBandwidthService;

/**
 * Reconciles a single server to its desired network state:
 *
 * - Under quota  -> enforce the persistent per-server speed cap (or unlimited),
 *                   and ensure the NIC is connected.
 * - Over quota   -> apply the resolved overage penalty, which always wins:
 *                   throttle to the penalty rate, or disconnect the NIC.
 *
 * Called per-server by {@see SyncServerRateLimitJob}; failures
 * propagate so the job (not this service) owns retry/isolation.
 *
 * See docs/bandwidth-rate-limiting-plan.md §3.
 */
class ServerRateLimitsSyncService
{
    public function __construct(
        private ServerNetworkBandwidthService $service,
        private OveragePenaltyResolver $resolver,
    ) {}

    /**
     * @throws RequestException
     * @throws ConfigModifiedException
     */
    public function sync(Server $server): void
    {
        if (! $server->isOverBandwidthQuota()) {
            // Under quota: enforce the speed cap and make sure we're connected.
            $this->service->apply($server, $server->speed_limit, linkDown: false);

            return;
        }

        $penalty = $this->resolver->for($server);

        if ($penalty->isDisconnect()) {
            $this->service->apply($server, $server->speed_limit, linkDown: true);

            return;
        }

        // Throttle: the penalty rate wins over the speed cap.
        $this->service->apply($server, $penalty->rate ?? $server->speed_limit, linkDown: false);
    }
}
