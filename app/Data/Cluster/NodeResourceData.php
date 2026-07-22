<?php

namespace App\Data\Cluster;

use Illuminate\Support\Arr;
use Spatie\LaravelData\Data;

/** Current host-level resource figures returned by `/cluster/resources`. */
class NodeResourceData extends Data
{
    public function __construct(
        public readonly string $nodeName,
        public readonly string $status,
        public readonly int $cpuCount,
        /** Fraction from 0 to 1. */
        public readonly float $cpuUsed,
        public readonly int $memoryTotal,
        public readonly int $memoryUsed,
        public readonly int $diskTotal,
        public readonly int $diskUsed,
        public readonly int $uptimeInSeconds,
    ) {}

    public static function fromRaw(array $raw): self
    {
        return new self(
            nodeName: Arr::get($raw, 'node'),
            status: Arr::get($raw, 'status', 'unknown'),
            cpuCount: (int) Arr::get($raw, 'maxcpu', 0),
            cpuUsed: (float) Arr::get($raw, 'cpu', 0),
            memoryTotal: (int) Arr::get($raw, 'maxmem', 0),
            memoryUsed: (int) Arr::get($raw, 'mem', 0),
            diskTotal: (int) Arr::get($raw, 'maxdisk', 0),
            diskUsed: (int) Arr::get($raw, 'disk', 0),
            uptimeInSeconds: (int) Arr::get($raw, 'uptime', 0),
        );
    }
}
