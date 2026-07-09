<?php

namespace App\Console\Commands\Maintenance;

use App\Services\Admin\OverviewService;
use App\Services\Metrics\VictoriaMetrics;
use Illuminate\Console\Command;

class SnapshotOverviewMetricsCommand extends Command
{
    protected $signature = 'metrics:snapshot';

    protected $description = 'Record admin overview metrics into VictoriaMetrics for history (deltas + sparklines).';

    public function handle(OverviewService $overview, VictoriaMetrics $metrics): void
    {
        if (! $metrics->enabled()) {
            $this->info('VictoriaMetrics is not configured; skipping metrics snapshot.');

            return;
        }

        $metrics->writeNow($overview->snapshotMetrics());

        $this->info('Recorded overview metrics snapshot.');
    }
}
