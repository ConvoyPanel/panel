<?php

namespace App\Data\Admin\Overview;

use Spatie\LaravelData\Data;

/**
 * Trend context for a single KPI: the change over the comparison window and a short series for a
 * sparkline. `delta` is null when there isn't enough history yet (e.g. a fresh install), so the UI
 * simply omits the trend rather than showing a misleading "0".
 */
class MetricTrendData extends Data
{
    public function __construct(
        /** Change vs. ~7 days ago (absolute for counts, points for percentages); null if unknown. */
        public ?float $delta,
        /** Chronological values for the sparkline, oldest first, current value last. */
        /** @var array<int, float> */
        public array $series,
    ) {}
}
