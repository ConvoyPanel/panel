<?php

namespace App\Data\Server\Proxmox;

use App\Enums\Server\State;
use Spatie\LaravelData\Data;

class ServerStateData extends Data
{
    public function __construct(
        public State $state,
        public float $cpuUsed,
        public int   $memoryTotal,
        public int   $memoryUsed,
        public int   $uptime,
    ) {
    }

    public static function fromRaw(array $raw): self
    {
        return new self(...[
            'state' => State::from($raw['status']),
            'uptime' => $raw['uptime'],
            'cpuUsed' => $raw['cpu'],
            'memoryTotal' => $raw['maxmem'],
            'memoryUsed' => $raw['mem'],
        ]);
    }
}
