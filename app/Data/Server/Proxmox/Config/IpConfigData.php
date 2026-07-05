<?php

namespace App\Data\Server\Proxmox\Config;

use Spatie\LaravelData\Data;

/**
 * A parsed PVE cloud-init `ipconfig{n}` property string
 * (`ip=<cidr>,gw=<addr>,ip6=<cidr>,gw6=<addr>`). Modelling it lets a config sync
 * compare the desired vs. stored ipconfig structurally, so an unchanged NIC isn't
 * rewritten (which would enqueue a redundant Proxmox "Configure" task).
 */
class IpConfigData extends Data
{
    public function __construct(
        public ?string $ip,
        public ?string $gateway,
        public ?string $ip6,
        public ?string $gateway6,
    ) {}

    public static function fromString(string $raw): self
    {
        $pairs = [];
        foreach (array_filter(explode(',', $raw)) as $part) {
            if (! str_contains($part, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $part, 2);
            $pairs[$key] = $value;
        }

        return new self(
            ip: $pairs['ip'] ?? null,
            gateway: $pairs['gw'] ?? null,
            ip6: $pairs['ip6'] ?? null,
            gateway6: $pairs['gw6'] ?? null,
        );
    }
}
