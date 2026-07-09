<?php

namespace App\Data\Admin\Overview;

use Spatie\LaravelData\Data;

/** Trend context for the KPI tiles, sourced from VictoriaMetrics history. */
class OverviewTrendsData extends Data
{
    public function __construct(
        public MetricTrendData $servers,
        public MetricTrendData $nodes,
        public MetricTrendData $users,
        public MetricTrendData $backups,
    ) {}
}
