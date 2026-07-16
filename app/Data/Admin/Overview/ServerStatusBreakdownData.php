<?php

namespace App\Data\Admin\Overview;

use Spatie\LaravelData\Data;

class ServerStatusBreakdownData extends Data
{
    /**
     * @param  array<string, int>  $statuses  Raw per-status counts (status value => count),
     *                                        so the UI can render buckets we don't call out explicitly.
     */
    public function __construct(
        public int $total,
        public int $ready,
        public int $installing,
        public int $suspended,
        public int $restoring,
        public int $deleting,
        public int $failed,
        public array $statuses,
    ) {}
}
