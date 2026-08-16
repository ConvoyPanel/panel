<?php

namespace App\Data\Server\Proxmox\Firewall;

use Illuminate\Support\Arr;
use Spatie\LaravelData\Data;

/**
 * A predefined Proxmox traffic macro (`SSH`, `HTTP`, ...) usable in place of a
 * protocol/port pair.
 *
 * The set varies by PVE version, so it is always read from the cluster rather
 * than hardcoded.
 */
class FirewallMacroData extends Data
{
    public function __construct(
        public string $name,
        public ?string $description,
    ) {}

    public static function fromRaw(array $raw): self
    {
        return new self(
            name: Arr::get($raw, 'macro'),
            description: Arr::get($raw, 'descr'),
        );
    }
}
