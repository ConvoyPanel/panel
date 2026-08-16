<?php

namespace App\Enums\Server\Firewall;

/**
 * What happens to traffic that no rule matched.
 *
 * Shares its vocabulary with {@see RuleAction} but is a separate concept: this
 * is the fallthrough for a whole direction, not a decision about one flow.
 */
enum FirewallPolicy: string
{
    case Accept = 'ACCEPT';
    case Drop = 'DROP';
    case Reject = 'REJECT';
}
