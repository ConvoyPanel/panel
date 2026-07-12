<?php

namespace App\Services\Servers;

use App\Data\Server\OveragePenaltyData;
use App\Enums\Server\OveragePenaltyAction;
use App\Models\Server;
use App\Settings\BandwidthSettings;

/**
 * Resolves the effective quota-overage penalty for a server by walking the
 * cascade: per-server override -> per-node override -> global BandwidthSettings.
 * Single source of truth so enforcement, the "effective value" UI hint, and
 * tests all agree. See docs/bandwidth-rate-limiting-plan.md §5.2.
 */
class OveragePenaltyResolver
{
    public function __construct(private BandwidthSettings $settings) {}

    public function for(Server $server): OveragePenaltyData
    {
        return $server->overage_penalty
            ?? $server->node->overage_penalty
            ?? $this->global();
    }

    /**
     * The global-tier default, materialized from the panel settings.
     */
    public function global(): OveragePenaltyData
    {
        return new OveragePenaltyData(
            action: OveragePenaltyAction::from($this->settings->overage_action),
            rate: $this->settings->overage_rate,
        );
    }
}
