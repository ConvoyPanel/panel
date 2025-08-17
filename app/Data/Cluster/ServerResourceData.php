<?php

namespace App\Data\Cluster;

use App\Enums\Server\LockStatus;
use Illuminate\Support\Arr;
use Spatie\LaravelData\Data;

class ServerResourceData extends Data
{
    public function __construct(
        // General
        public readonly string $id,
        public readonly ?string $name,
        public readonly string  $status,
        public readonly bool    $isTemplate,
        public readonly int    $vmid,
        public readonly ?string $nodeName,
        public readonly ?string $poolName,
        public readonly array   $tags,

        // CPU
        public readonly int    $maxCpuCount,
        public readonly float  $cpuUsed,

        // Memory
        public readonly int    $maxMemory,
        public readonly int    $memoryUsed,
        public readonly int    $memoryUsedByHost,

        // Disk
        public readonly int    $maxDiskSpace,
        public readonly int    $diskSpaceUsed,
        public readonly int    $diskRead,
        public readonly int $diskWrite,

        // Network
        public readonly int $networkIn,
        public readonly int $networkOut,

        // Other
        public readonly int $uptimeInSeconds,
        public readonly ?LockStatus $lockStatus,
        public readonly ?string $haState,
    ) {
    }

    public static function fromRaw(array $raw): self
    {
        $tags = Arr::get($raw, 'tags');
        $lockStatus = Arr::get($raw, 'lock');

        return new self(
            id              : Arr::get($raw, 'id'),
            name            : Arr::get($raw, 'name'),
            status          : Arr::get($raw, 'status'),
            isTemplate      : Arr::get($raw, 'template', false),
            vmid            : Arr::get($raw, 'vmid'),
            nodeName        : Arr::get($raw, 'node'),
            poolName        : Arr::get($raw, 'pool'),
            tags            : filled($tags) ? explode(',', $tags) : [],
            maxCpuCount     : Arr::get($raw, 'maxcpu', 0),
            cpuUsed         : Arr::get($raw, 'cpu', 0.0),
            maxMemory       : Arr::get($raw, 'maxmem', 0),
            memoryUsed      : Arr::get($raw, 'mem', 0),
            memoryUsedByHost: Arr::get($raw, 'memhost', 0),
            maxDiskSpace    : Arr::get($raw, 'maxdisk', 0),
            diskSpaceUsed   : Arr::get($raw, 'disk', 0),
            diskRead        : Arr::get($raw, 'diskread', 0),
            diskWrite       : Arr::get($raw, 'diskwrite', 0),
            networkIn       : Arr::get($raw, 'netin', 0),
            networkOut      : Arr::get($raw, 'netout', 0),
            uptimeInSeconds : Arr::get($raw, 'uptime', 0),
            lockStatus      : filled($lockStatus) ? LockStatus::tryFrom(Arr::get($raw, 'lock')) : null,
            haState         : Arr::get($raw, 'hastate'),
        );
    }
}
