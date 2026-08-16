<?php

namespace App\Enums\Server\Firewall;

/**
 * Which way a firewall rule looks at traffic.
 *
 * Proxmox also accepts `forward` and `group` here. Neither is modelled: a
 * client-side rule only ever describes traffic entering or leaving its own
 * server, and `group` is a reference to a datacenter-level security group,
 * which is an operator concern rather than a tenant one.
 */
enum RuleDirection: string
{
    case Inbound = 'in';
    case Outbound = 'out';
}
