<?php

namespace App\Data\Server\Proxmox;

use Spatie\LaravelData\Data;
use App\Enums\Server\State;

class ServerStateData extends Data
{
    public function __construct(
        public State $state,
        public float $cpu_used,
        public int   $memory_total,
        public int   $memory_used,
        public int   $uptime,
    ) {
    }

    public static function fromRaw(array $raw)
    {
        return new self(...[
            'state' => State::from($raw['status']),
            'uptime' => $raw['uptime'],
            'cpu_used' => $raw['cpu'],
            'memory_total' => $raw['maxmem'],
            'memory_used' => $raw['mem'],
        ]);
    }
}
