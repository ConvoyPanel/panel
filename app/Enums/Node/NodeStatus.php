<?php

namespace App\Enums\Node;

enum NodeStatus: string
{
    /** Convoy's last check reached the node's API and it answered. */
    case ONLINE = 'online';

    /** The last check failed; `status_code` says why. */
    case UNREACHABLE = 'unreachable';

    /**
     * Convoy has not checked recently enough to say.
     *
     * A first-class state, not a placeholder: it is what a node reads as before
     * its first poll, and what every node reads as when the scheduler is not
     * running. Never collapse it into `unreachable` -- "we asked and got
     * nothing" and "we have not asked" are different facts, and only one of them
     * is about the node.
     */
    case UNKNOWN = 'unknown';
}
