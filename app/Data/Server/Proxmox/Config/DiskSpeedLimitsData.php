<?php

namespace App\Data\Server\Proxmox\Config;

use Spatie\LaravelData\Data;

class DiskSpeedLimitsData extends Data
{
    public function __construct(
        /**
         * @var $limit
         *
         * Maximum r/w speed in bytes per second.
         */
        public ?int $limit,

        /**
         * @var $read
         *
         * Maximum read speed in bytes per second.
         */
        public ?int $read,

        /**
         * @var $write
         *
         * Maximum write speed in bytes per second.
         */
        public ?int $write,
    ) {}
}
