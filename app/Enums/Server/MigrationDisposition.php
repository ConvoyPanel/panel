<?php

namespace App\Enums\Server;

/**
 * What happens to a server's addresses if it moves to a particular node.
 *
 * Computed, never asserted by the operator. Convoy already records which pools
 * a bridge fronts, so "the IP follows" is a question the panel can answer, and
 * an operator ticking a "preserve IP" box the way Virtualizor and SolusVM ask
 * them to is asserting something they cannot see.
 */
enum MigrationDisposition: string
{
    /**
     * The destination has a bridge of the same name attached to every pool the
     * server's addresses live in. Nothing about the guest's networking changes,
     * and a running guest can move online.
     */
    case Preserve = 'preserve';

    /**
     * The destination genuinely fronts different networks. The addresses go
     * back to their pool and the server gets new ones from the destination
     * bridge. The guest's IP changes and it has to be restarted for cloud-init
     * to re-apply it.
     */
    case Reallocate = 'reallocate';

    /**
     * Refused, with a reason. Either PVE will not send the guest there, or the
     * destination has a same-named bridge that simply is not attached to the
     * pool, which is an incomplete topology model rather than a different
     * network. Guessing between those two is exactly what breaks a guest.
     */
    case Blocked = 'blocked';
}
