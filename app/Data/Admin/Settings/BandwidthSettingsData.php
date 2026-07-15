<?php

namespace App\Data\Admin\Settings;

use App\Data\Server\OveragePenaltyData;
use Spatie\LaravelData\Data;

/**
 * The panel-wide bandwidth defaults, as edited on the admin Settings screen.
 * This is the top ("global") tier of the overage-penalty cascade, so unlike the
 * node and server tiers it can never be "inherit" — there is nothing above it.
 * See docs/bandwidth-rate-limiting-plan.md §5.
 */
class BandwidthSettingsData extends Data
{
    public function __construct(
        public OveragePenaltyData $overagePenalty,
    ) {}
}
