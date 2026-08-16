<?php

namespace App\Enums\Server\Firewall;

/**
 * What a firewall rule does with traffic it matches.
 *
 * Proxmox leaves this field unconstrained because it doubles as a security
 * group name. Narrowing it to these three is deliberate -- see
 * {@see RuleDirection} for why groups stay out of the client surface.
 */
enum RuleAction: string
{
    case Accept = 'ACCEPT';

    /** Silently discards the packet; the sender waits for a timeout. */
    case Drop = 'DROP';

    /** Sends a refusal back, so the sender fails immediately. */
    case Reject = 'REJECT';
}
