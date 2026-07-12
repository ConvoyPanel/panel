<?php

namespace App\Data\Node\Status;

use App\Enums\Node\BootMode;
use Spatie\LaravelData\Data;

class NodeStatusData extends Data
{
    public function __construct(
        public KernelInfoData $kernel,
        public CpuInfoData $cpu,
        public float $cpuUsage,
        public array $loadAverage,
        public MemoryUsageData $memory,
        public MemoryUsageData $swap,
        public FilesystemUsageData $rootFilesystem,
        public BootInfoData $boot,
        public string $pveVersion,
        public int $uptimeSeconds,
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
                flags: (string) ($raw['cpuinfo']['flags'] ?? '')
            ),
            cpuUsage: (float) $raw['cpu'],
            loadAverage: array_map('floatval', $raw['loadavg']),
            memory: new MemoryUsageData(
                used : $raw['memory']['used'],
                free : $raw['memory']['free'],
                total: $raw['memory']['total'],
                available: $raw['memory']['available'] ?? null,
            ),
            swap: new MemoryUsageData(
                used : $raw['swap']['used'] ?? 0,
                free : $raw['swap']['free'] ?? 0,
                total: $raw['swap']['total'] ?? 0,
            ),
            rootFilesystem: new FilesystemUsageData(
                used: $raw['rootfs']['used'],
                free: $raw['rootfs']['free'],
                available: $raw['rootfs']['avail'],
                total: $raw['rootfs']['total'],
            ),
            boot: new BootInfoData(
                mode: BootMode::from($raw['boot-info']['mode']),
                secureBoot: $raw['boot-info']['secureboot'] ?? null,
            ),
            pveVersion: $raw['pveversion'],
            uptimeSeconds: (int) ($raw['uptime'] ?? 0),
        );
    }
}
