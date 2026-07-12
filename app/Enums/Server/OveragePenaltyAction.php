<?php

namespace App\Enums\Server;

/**
 * What Convoy does to a server that has exceeded its monthly bandwidth quota.
 * See docs/bandwidth-rate-limiting-plan.md §5.
 */
enum OveragePenaltyAction: string
{
    /** Cap every NIC at the penalty's rate (bytes/s). */
    case THROTTLE = 'throttle';

    /** Disconnect every NIC (link_down) — reversible, the guest keeps the NIC. */
    case DISCONNECT = 'disconnect';
}
