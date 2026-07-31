<?php

namespace App\Data\Admin\Overview;

use Spatie\LaravelData\Data;

/**
 * Fleet-wide server counts, across two independent axes.
 *
 * `$lifecycles` (and the named buckets drawn from it) partition the fleet -- every server is
 * in exactly one. `$suspended` does not: it counts along a separate axis and overlaps every
 * bucket, so it must not be charted as a slice alongside them or added into `$total`.
 */
class ServerBreakdownData extends Data
{
    /**
     * @param  array<string, int>  $lifecycles  Raw per-lifecycle counts (lifecycle value => count),
     *                                          so the UI can render buckets we don't call out explicitly.
     */
    public function __construct(
        public int $total,
        public int $ready,
        public int $installing,
        public int $restoring,
        public int $deleting,
        public int $failed,
        public int $suspended,
        public array $lifecycles,
    ) {}
}
