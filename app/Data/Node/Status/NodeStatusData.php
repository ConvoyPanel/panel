<?php

namespace App\Data\Node\Status;

use Carbon\CarbonInterval;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\WithTransformer;
use App\Extensions\Spatie\Data\CarbonIntervalTransformer;
use Spatie\LaravelData\Transformers\DateTimeInterfaceTransformer;

class NodeStatusData extends Data
{
    public function __construct(
        public KernelInfoData $kernel,
        public CpuInfoData $cpu,
        public MemoryUsageData $memory,
        public MemoryUsageData $swap,
        #[WithTransformer(CarbonIntervalTransformer::class)]
        public CarbonInterval $uptime,
    ) {}

    public static function fromRaw(array $raw): self
    {
        return new self(
            kernel: new KernelInfoData(
                build: $raw['current-kernel']['version'],
                release: $raw['current-kernel']['release'],
                os: $raw['current-kernel']['sysname'],
                architecture: $raw['current-kernel']['machine']
            ),
            cpu: new CpuInfoData(
                cpuCount: $raw['cpuinfo']['cpus'],
                socketCount: $raw['cpuinfo']['sockets'],
                coreCount: $raw['cpuinfo']['cores'],
                model: $raw['cpuinfo']['model'],
                flags: $raw['cpuinfo']['flags']
            ),
            memory: new MemoryUsageData(
                used : $raw['memory']['used'],
                free : $raw['memory']['free'],
                total: $raw['memory']['total']
            ),
            swap: new MemoryUsageData(
                used : $raw['swap']['used'],
                free : $raw['swap']['free'],
                total: $raw['swap']['total']
            ),
            uptime: CarbonInterval::seconds($raw['uptime'])->cascade()
        );
    }
}
