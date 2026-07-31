<?php

namespace App\Data\Server\Proxmox;

use App\Data\Server\Power\PendingPowerActionData;
use App\Data\Server\Power\PowerActionResultData;
use App\Enums\Server\PowerState;
use Spatie\LaravelData\Data;

class ServerStateData extends Data
{
    public function __construct(
        public PowerState $powerState,
        public float $cpuUsed,
        public int $memoryTotal,
        public int $memoryUsed,
        public int $uptime,
        // Populated by the controller from ServerPowerLockService — not part of
        // the raw Proxmox status, so fromRaw() leaves them null.
        public ?PendingPowerActionData $pendingPowerAction = null,
        public ?PowerActionResultData $lastPowerAction = null,
    ) {}

    public static function fromRaw(array $raw): self
    {
        return new self(...[
            'powerState' => PowerState::from($raw['status']),
            'uptime' => $raw['uptime'],
            'cpuUsed' => $raw['cpu'],
            'memoryTotal' => $raw['maxmem'],
            'memoryUsed' => $raw['mem'],
        ]);
    }
}
